// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

contract VehicleRegistry {
    struct Vehicle {
        string vin;
        uint256 mileage;
        uint256 lastUpdate;
        bool isActive;
    }

    struct Maintenance {
        uint256 vehicleId;
        string description;
        uint256 timestamp;
        address mechanic;
        string partsChanged;
    }

    struct MileageProof {
        uint256 vehicleId;
        uint256 mileage;
        uint256 timestamp;
        address recorder;
    }

    mapping(uint256 => Vehicle) public vehicles;
    mapping(uint256 => Maintenance[]) public maintenanceHistory;
    mapping(uint256 => MileageProof[]) public mileageHistory;
    mapping(address => bool) public authorizedGarages;
    mapping(uint256 => PendingTransfer) public pendingTransfers;

    uint256 public vehicleCounter;
    address public admin;

    struct PendingTransfer {
        uint256 vehicleId;
        address from;
        address to;
        uint256 price;
        uint256 timestamp;
        bool fromApproved;
        bool toApproved;
    }

    event VehicleRegistered(uint256 indexed id, string vin, address registrant);
    event MileageUpdated(uint256 indexed vehicleId, uint256 newMileage, address recorder);
    event MaintenanceRecorded(uint256 indexed vehicleId, string description, address mechanic);
    event GarageAuthorized(address garage, bool status);
    event TransferInitiated(uint256 indexed transferId, uint256 indexed vehicleId, address from, address to);
    event TransferApproved(uint256 indexed transferId, address approver);
    event TransferCompleted(uint256 indexed transferId, uint256 indexed vehicleId, address from, address to);
    event TransferCancelled(uint256 indexed transferId);

    modifier onlyAdmin() {
        require(msg.sender == admin, "Only admin can perform this action");
        _;
    }

    modifier onlyAuthorizedGarage() {
        require(authorizedGarages[msg.sender], "Only authorized garages can perform this action");
        _;
    }

    modifier vehicleExists(uint256 _vehicleId) {
        require(vehicles[_vehicleId].isActive, "Vehicle does not exist");
        _;
    }

    constructor() {
        admin = msg.sender;
    }

    function registerVehicle(string memory _vin, uint256 _initialMileage) public onlyAdmin {
        vehicleCounter++;
        vehicles[vehicleCounter] = Vehicle({
            vin: _vin,
            mileage: _initialMileage,
            lastUpdate: block.timestamp,
            isActive: true
        });

        emit VehicleRegistered(vehicleCounter, _vin, msg.sender);
    }

    function updateMileage(uint256 _vehicleId, uint256 _newMileage) public 
        vehicleExists(_vehicleId) 
    {
        require(_newMileage > vehicles[_vehicleId].mileage, "New mileage must be greater than current");
        
        vehicles[_vehicleId].mileage = _newMileage;
        vehicles[_vehicleId].lastUpdate = block.timestamp;

        mileageHistory[_vehicleId].push(MileageProof({
            vehicleId: _vehicleId,
            mileage: _newMileage,
            timestamp: block.timestamp,
            recorder: msg.sender
        }));

        emit MileageUpdated(_vehicleId, _newMileage, msg.sender);
    }

    function recordMaintenance(
        uint256 _vehicleId, 
        string memory _description, 
        string memory _partsChanged
    ) public 
        vehicleExists(_vehicleId) 
        onlyAuthorizedGarage 
    {
        maintenanceHistory[_vehicleId].push(Maintenance({
            vehicleId: _vehicleId,
            description: _description,
            timestamp: block.timestamp,
            mechanic: msg.sender,
            partsChanged: _partsChanged
        }));

        emit MaintenanceRecorded(_vehicleId, _description, msg.sender);
    }

    function authorizeGarage(address _garage, bool _status) public onlyAdmin {
        authorizedGarages[_garage] = _status;
        emit GarageAuthorized(_garage, _status);
    }

    function getVehicle(uint256 _vehicleId) public view returns (
        string memory vin,
        uint256 mileage,
        uint256 lastUpdate,
        bool isActive
    ) {
        Vehicle memory v = vehicles[_vehicleId];
        return (v.vin, v.mileage, v.lastUpdate, v.isActive);
    }

    function getMaintenanceHistory(uint256 _vehicleId) public view returns (Maintenance[] memory) {
        return maintenanceHistory[_vehicleId];
    }

    function getMileageHistory(uint256 _vehicleId) public view returns (MileageProof[] memory) {
        return mileageHistory[_vehicleId];
    }

    function getVehicleCount() public view returns (uint256) {
        return vehicleCounter;
    }

    // Double signature functions for critical transactions
    function initiateTransfer(uint256 _vehicleId, address _to, uint256 _price) public 
        vehicleExists(_vehicleId) 
    {
        require(vehicles[_vehicleId].isActive, "Vehicle must be active");
        require(_to != msg.sender, "Cannot transfer to yourself");
        
        uint256 transferId = vehicleCounter + 1; // Use vehicle counter as transfer ID for simplicity
        
        pendingTransfers[transferId] = PendingTransfer({
            vehicleId: _vehicleId,
            from: msg.sender,
            to: _to,
            price: _price,
            timestamp: block.timestamp,
            fromApproved: false,
            toApproved: false
        });
        
        emit TransferInitiated(transferId, _vehicleId, msg.sender, _to);
    }

    function approveTransfer(uint256 _transferId) public {
        PendingTransfer storage transfer = pendingTransfers[_transferId];
        require(transfer.timestamp > 0, "Transfer does not exist");
        require(transfer.fromApproved == false || transfer.toApproved == false, "Transfer already completed or cancelled");
        
        if (msg.sender == transfer.from) {
            transfer.fromApproved = true;
        } else if (msg.sender == transfer.to) {
            transfer.toApproved = true;
        } else {
            revert("Only parties involved can approve");
        }
        
        emit TransferApproved(_transferId, msg.sender);
        
        // If both approved, complete the transfer
        if (transfer.fromApproved && transfer.toApproved) {
            completeTransfer(_transferId);
        }
    }

    function completeTransfer(uint256 _transferId) internal {
        PendingTransfer storage transfer = pendingTransfers[_transferId];
        
        // Update vehicle ownership (in a real implementation, you'd have an owner field)
        // For now, we just emit the event
        emit TransferCompleted(_transferId, transfer.vehicleId, transfer.from, transfer.to);
        
        // Clean up
        delete pendingTransfers[_transferId];
    }

    function cancelTransfer(uint256 _transferId) public {
        PendingTransfer storage transfer = pendingTransfers[_transferId];
        require(transfer.timestamp > 0, "Transfer does not exist");
        require(msg.sender == transfer.from || msg.sender == transfer.to, "Only parties can cancel");
        require(!transfer.fromApproved || !transfer.toApproved, "Cannot cancel approved transfer");
        
        emit TransferCancelled(_transferId);
        delete pendingTransfers[_transferId];
    }

    function getPendingTransfer(uint256 _transferId) public view returns (
        uint256 vehicleId,
        address from,
        address to,
        uint256 price,
        uint256 timestamp,
        bool fromApproved,
        bool toApproved
    ) {
        PendingTransfer memory transfer = pendingTransfers[_transferId];
        return (
            transfer.vehicleId,
            transfer.from,
            transfer.to,
            transfer.price,
            transfer.timestamp,
            transfer.fromApproved,
            transfer.toApproved
        );
    }
}
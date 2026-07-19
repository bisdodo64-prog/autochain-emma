const VehicleRegistry = artifacts.require("VehicleRegistry");

const { expect } = require("chai");

contract("VehicleRegistry", (accounts) => {
  const [admin, garage, user1, user2] = accounts;
  let contract;

  beforeEach(async () => {
    contract = await VehicleRegistry.new({ from: admin });
  });

  describe("Deployment", () => {
    it("should deploy the contract successfully", async () => {
      assert(contract.address !== "");
    });

    it("should set the deployer as admin", async () => {
      const contractAdmin = await contract.admin();
      assert.equal(contractAdmin, admin);
    });
  });

  describe("Vehicle Registration", () => {
    it("should allow admin to register a vehicle", async () => {
      const vin = "TEST1234567890";
      const initialMileage = 1000;

      await contract.registerVehicle(vin, initialMileage, { from: admin });

      const vehicleCount = await contract.getVehicleCount();
      assert.equal(vehicleCount.toNumber(), 1);

      const vehicle = await contract.getVehicle(1);
      assert.equal(vehicle[0], vin);
      assert.equal(vehicle[1].toNumber(), initialMileage);
      assert.equal(vehicle[3], true);
    });

    it("should not allow non-admin to register a vehicle", async () => {
      const vin = "TEST1234567890";
      const initialMileage = 1000;

      try {
        await contract.registerVehicle(vin, initialMileage, { from: user1 });
        assert.fail("Should have thrown an error");
      } catch (error) {
        assert(error.message.includes("Only admin can perform this action"));
      }
    });

    it("should emit VehicleRegistered event", async () => {
      const vin = "TEST1234567890";
      const initialMileage = 1000;

      const result = await contract.registerVehicle(vin, initialMileage, { from: admin });

      const event = result.logs[0];
      assert.equal(event.event, "VehicleRegistered");
      assert.equal(event.args.id.toNumber(), 1);
      assert.equal(event.args.vin, vin);
      assert.equal(event.args.registrant, admin);
    });
  });

  describe("Mileage Update", () => {
    beforeEach(async () => {
      await contract.registerVehicle("TEST1234567890", 1000, { from: admin });
    });

    it("should allow updating mileage", async () => {
      await contract.updateMileage(1, 2000, { from: admin });

      const vehicle = await contract.getVehicle(1);
      assert.equal(vehicle[1].toNumber(), 2000);
    });

    it("should not allow decreasing mileage", async () => {
      try {
        await contract.updateMileage(1, 500, { from: admin });
        assert.fail("Should have thrown an error");
      } catch (error) {
        assert(error.message.includes("New mileage must be greater than current"));
      }
    });

    it("should record mileage history", async () => {
      await contract.updateMileage(1, 2000, { from: admin });
      await contract.updateMileage(1, 3000, { from: admin });

      const history = await contract.getMileageHistory(1);
      assert.equal(history.length, 2);
      assert.equal(history[0].mileage.toNumber(), 2000);
      assert.equal(history[1].mileage.toNumber(), 3000);
    });

    it("should emit MileageUpdated event", async () => {
      const result = await contract.updateMileage(1, 2000, { from: admin });

      const event = result.logs[0];
      assert.equal(event.event, "MileageUpdated");
      assert.equal(event.args.vehicleId.toNumber(), 1);
      assert.equal(event.args.newMileage.toNumber(), 2000);
    });
  });

  describe("Garage Authorization", () => {
    it("should allow admin to authorize a garage", async () => {
      await contract.authorizeGarage(garage, true, { from: admin });

      const isAuthorized = await contract.authorizedGarages(garage);
      assert.equal(isAuthorized, true);
    });

    it("should allow admin to revoke garage authorization", async () => {
      await contract.authorizeGarage(garage, true, { from: admin });
      await contract.authorizeGarage(garage, false, { from: admin });

      const isAuthorized = await contract.authorizedGarages(garage);
      assert.equal(isAuthorized, false);
    });

    it("should not allow non-admin to authorize garage", async () => {
      try {
        await contract.authorizeGarage(garage, true, { from: user1 });
        assert.fail("Should have thrown an error");
      } catch (error) {
        assert(error.message.includes("Only admin can perform this action"));
      }
    });
  });

  describe("Maintenance Recording", () => {
    beforeEach(async () => {
      await contract.registerVehicle("TEST1234567890", 1000, { from: admin });
      await contract.authorizeGarage(garage, true, { from: admin });
    });

    it("should allow authorized garage to record maintenance", async () => {
      const description = "Oil change";
      const partsChanged = "Oil filter, engine oil";

      await contract.recordMaintenance(1, description, partsChanged, { from: garage });

      const history = await contract.getMaintenanceHistory(1);
      assert.equal(history.length, 1);
      assert.equal(history[0].description, description);
      assert.equal(history[0].mechanic, garage);
    });

    it("should not allow unauthorized garage to record maintenance", async () => {
      try {
        await contract.recordMaintenance(1, "Oil change", "Oil filter", { from: user1 });
        assert.fail("Should have thrown an error");
      } catch (error) {
        assert(error.message.includes("Only authorized garages can perform this action"));
      }
    });

    it("should emit MaintenanceRecorded event", async () => {
      const result = await contract.recordMaintenance(1, "Oil change", "Oil filter", { from: garage });

      const event = result.logs[0];
      assert.equal(event.event, "MaintenanceRecorded");
      assert.equal(event.args.vehicleId.toNumber(), 1);
      assert.equal(event.args.description, "Oil change");
    });
  });

  describe("Transfer with Double Signature", () => {
    beforeEach(async () => {
      await contract.registerVehicle("TEST1234567890", 1000, { from: admin });
    });

    it("should allow initiating a transfer", async () => {
      const result = await contract.initiateTransfer(1, user2, 1000, { from: user1 });

      const event = result.logs[0];
      assert.equal(event.event, "TransferInitiated");
      assert.equal(event.args.from, user1);
      assert.equal(event.args.to, user2);
    });

    it("should not allow transfer to self", async () => {
      try {
        await contract.initiateTransfer(1, user1, 1000, { from: user1 });
        assert.fail("Should have thrown an error");
      } catch (error) {
        assert(error.message.includes("Cannot transfer to yourself"));
      }
    });

    it("should allow both parties to approve transfer", async () => {
      await contract.initiateTransfer(1, user2, 1000, { from: user1 });
      await contract.approveTransfer(2, { from: user1 }); // transferId = vehicleCounter + 1 = 2
      await contract.approveTransfer(2, { from: user2 });

      const transfer = await contract.getPendingTransfer(2);
      assert.equal(transfer.fromApproved, true);
      assert.equal(transfer.toApproved, true);
    });

    it("should allow cancelling transfer", async () => {
      await contract.initiateTransfer(1, user2, 1000, { from: user1 });

      await contract.cancelTransfer(2, { from: user1 });

      try {
        await contract.getPendingTransfer(2);
        assert.fail("Should have thrown an error");
      } catch (error) {
        // Transfer should be deleted
      }
    });

    it("should not allow cancelling approved transfer", async () => {
      await contract.initiateTransfer(1, user2, 1000, { from: user1 });
      await contract.approveTransfer(2, { from: user1 });

      try {
        await contract.cancelTransfer(2, { from: user1 });
        assert.fail("Should have thrown an error");
      } catch (error) {
        assert(error.message.includes("Cannot cancel approved transfer"));
      }
    });
  });
});
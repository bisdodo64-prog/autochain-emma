const STATUS_MAP = {
  available: { label: 'Disponible', className: 'available' },
  in_mission: { label: 'En mission', className: 'mission' },
  maintenance: { label: 'En maintenance', className: 'maintenance' },
  out_of_service: { label: 'Hors service', className: 'maintenance' },
  Disponible: { label: 'Disponible', className: 'available' },
  'En mission': { label: 'En mission', className: 'mission' },
  'En maintenance': { label: 'En maintenance', className: 'maintenance' }
}

const STATUS_TO_API = {
  available: 'available',
  Disponible: 'available',
  in_mission: 'in_mission',
  'En mission': 'in_mission',
  mission: 'in_mission',
  maintenance: 'maintenance',
  'En maintenance': 'maintenance'
}

export const toApiStatus = (status) => STATUS_TO_API[status] || 'available'

export const normalizeVehicle = (vehicle) => {
  const status = vehicle?.status || 'available'
  const mapped = STATUS_MAP[status] || STATUS_MAP.available
  return {
    id: vehicle?.id,
    brand: vehicle?.brand || 'Véhicule',
    model: vehicle?.model || 'Non défini',
    year: vehicle?.year || '2024',
    licensePlate: vehicle?.plate_number || vehicle?.licensePlate || 'N/A',
    mileage: Number(vehicle?.current_mileage ?? vehicle?.mileage ?? vehicle?.initial_mileage ?? 0),
    purchasePrice: Number(vehicle?.purchase_price ?? vehicle?.purchasePrice ?? 0),
    status: mapped.label,
    statusClass: mapped.className,
    blockchainVerified: Boolean(vehicle?.blockchain_id || vehicle?.blockchainVerified),
    vin: vehicle?.vin || '',
    fuelType: vehicle?.fuelType || 'Hybride',
    serviceDate: vehicle?.serviceDate || '15/03/2024',
    transmission: vehicle?.transmission || 'Automatique',
    statusIcon:
      mapped.className === 'available'
        ? 'fas fa-check-circle'
        : mapped.className === 'mission'
          ? 'fas fa-truck'
          : 'fas fa-tools'
  }
}

export const buildVehiclePayload = (form) => ({
  vin: form.vin || `VIN${Date.now()}`,
  brand: form.brand,
  model: form.model,
  year: Number(form.year || new Date().getFullYear()),
  plate_number: form.licensePlate || form.plate_number || `TMP-${Date.now()}`,
  initial_mileage: parseInt(form.mileage, 10) || 0,
  purchase_price: form.purchasePrice != null && form.purchasePrice !== ''
    ? Number(form.purchasePrice)
    : null
})

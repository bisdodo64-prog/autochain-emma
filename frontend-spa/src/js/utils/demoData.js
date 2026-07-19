export const DEMO_VEHICLES = [
  {
    id: 1,
    brand: 'Toyota',
    model: 'Corolla',
    year: 2022,
    plate_number: 'AA-123-BB',
    current_mileage: 45000,
    status: 'available',
    blockchain_id: 1,
    vin: 'VF4JKL11223344556'
  },
  {
    id: 2,
    brand: 'Renault',
    model: 'Clio',
    year: 2021,
    plate_number: 'CC-456-DD',
    current_mileage: 78000,
    status: 'in_mission',
    blockchain_id: 2,
    vin: 'VF1ABC12345678901'
  },
  {
    id: 3,
    brand: 'Peugeot',
    model: '308',
    year: 2023,
    plate_number: 'EE-789-FF',
    current_mileage: 12000,
    status: 'maintenance',
    blockchain_id: null,
    vin: 'VF2DEF98765432109'
  },
  {
    id: 4,
    brand: 'Citroën',
    model: 'C3',
    year: 2020,
    plate_number: 'GG-012-HH',
    current_mileage: 95000,
    status: 'available',
    blockchain_id: 3,
    vin: 'VF3GHI56789012345'
  },
  {
    id: 5,
    brand: 'BMW',
    model: 'Serie 3',
    year: 2024,
    plate_number: 'II-345-JJ',
    current_mileage: 5000,
    status: 'in_mission',
    blockchain_id: 4,
    vin: 'VF5MNO99887766554'
  }
]

export const DEMO_INTERVENTIONS = [
  {
    id: 1,
    vehicle: 'Peugeot 308',
    description: 'Vidange moteur + filtres',
    urgency: 'urgent',
    urgencyLabel: 'Urgent',
    status: 'En attente',
    parts: [],
    certified: false
  },
  {
    id: 2,
    vehicle: 'Citroën C3',
    description: 'Plaquettes de frein avant',
    urgency: 'normal',
    urgencyLabel: 'Normal',
    status: 'En attente',
    parts: [],
    certified: false
  },
  {
    id: 3,
    vehicle: 'Toyota Corolla',
    description: 'Révision 45 000 km',
    urgency: 'low',
    urgencyLabel: 'Planifié',
    status: 'En attente',
    parts: [],
    certified: false
  }
]

export const DEMO_DRIVERS = [
  { id: 1, name: 'Jean Dupont', initials: 'JD', role: 'Chauffeur', email: 'jean@autochain.com', phone: '06 12 34 56 78', vehicle: 'Renault Clio (CC-456-DD)', missions: 156, km: '45 200 km', rating: 4.8, active: true, color: '#2563eb' },
  { id: 2, name: 'Marie Martin', initials: 'MM', role: 'Chauffeur', email: 'marie@autochain.com', phone: '06 98 76 54 32', vehicle: 'BMW Serie 3 (II-345-JJ)', missions: 89, km: '23 800 km', rating: 4.9, active: true, color: '#7c3aed' },
  { id: 3, name: 'Paul Durand', initials: 'PD', role: 'Chauffeur', email: 'paul@autochain.com', phone: '06 45 67 89 01', vehicle: null, missions: 34, km: '8 500 km', rating: 4.5, active: false, color: '#f59e0b' },
  { id: 4, name: 'Sophie Bernard', initials: 'SB', role: 'Chauffeur', email: 'sophie@autochain.com', phone: '06 11 22 33 44', vehicle: 'Toyota Corolla (AA-123-BB)', missions: 201, km: '67 300 km', rating: 4.7, active: true, color: '#10b981' }
]

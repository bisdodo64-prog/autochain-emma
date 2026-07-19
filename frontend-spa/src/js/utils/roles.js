const BACKEND_TO_FRONTEND = {
  super_admin: 'super_admin',
  fleet_manager: 'gestionnaire_parc',
  driver: 'chauffeur',
  garage: 'garagiste_agree',
  auditor: 'auditeur'
}

const FRONTEND_TO_BACKEND = Object.fromEntries(
  Object.entries(BACKEND_TO_FRONTEND).map(([backend, frontend]) => [frontend, backend])
)

export const ROLE_LABELS = {
  super_admin: 'Super Admin',
  gestionnaire_parc: 'Gestionnaire',
  chauffeur: 'Chauffeur',
  garagiste_agree: 'Garagiste',
  auditeur: 'Auditeur',
  fleet_manager: 'Gestionnaire',
  driver: 'Chauffeur',
  garage: 'Garagiste',
  auditor: 'Auditeur'
}

export const mapBackendRole = (roleName) => BACKEND_TO_FRONTEND[roleName] || roleName

export const mapFrontendRole = (roleName) => FRONTEND_TO_BACKEND[roleName] || roleName

export const normalizeUser = (user) => {
  if (!user) return null
  const roles = (user.roles || []).map((role) => ({
    ...role,
    name: mapBackendRole(role.name)
  }))
  return { ...user, roles }
}

export const getPrimaryRole = (user) => user?.roles?.[0]?.name || null

export const getRoleLabel = (roleName) => ROLE_LABELS[roleName] || roleName

export const isSuperAdmin = (user) =>
  getPrimaryRole(user) === 'super_admin' ||
  (user?.roles || []).some((role) => mapBackendRole(role.name) === 'super_admin')

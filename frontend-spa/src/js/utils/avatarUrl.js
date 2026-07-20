const API_URL = import.meta.env.VITE_API_URL || '/api'

/**
 * Resolve an avatar URL for <img src>.
 * Backend may return a relative path (/api/users/{id}/avatar); on Render the SPA
 * and API are on different hosts, so we rewrite using VITE_API_URL when absolute.
 */
export function resolveAvatarUrl(url, cacheBust) {
  if (!url) return null

  const raw = String(url).trim()
  if (!raw) return null
  if (raw.startsWith('data:') || raw.startsWith('blob:')) return raw

  let resolved = raw

  if (/^https?:\/\//i.test(raw)) {
    resolved = raw
  } else if (raw.startsWith('/')) {
    if (/^https?:\/\//i.test(API_URL)) {
      const origin = API_URL.replace(/\/api\/?$/i, '').replace(/\/$/, '')
      resolved = `${origin}${raw}`
    } else {
      resolved = raw
    }
  } else {
    const base = String(API_URL).replace(/\/$/, '')
    resolved = `${base}/${raw.replace(/^\//, '')}`
  }

  if (cacheBust != null && cacheBust !== '') {
    const sep = resolved.includes('?') ? '&' : '?'
    resolved = `${resolved}${sep}t=${encodeURIComponent(String(cacheBust))}`
  }

  return resolved
}

export function getInitials(name, fallback = 'U') {
  const parts = String(name || '').trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return fallback
  const initials = parts.map((p) => p[0] || '').join('').toUpperCase().slice(0, 2)
  return initials || fallback
}

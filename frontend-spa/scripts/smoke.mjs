/**
 * Smoke test frontend (Node natif, sans Vitest).
 * Usage: node scripts/smoke.mjs
 */
import { readFileSync, existsSync } from 'fs'
import { resolve, dirname } from 'path'
import { fileURLToPath } from 'url'

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const required = [
  'src/js/router.js',
  'src/js/api.js',
  'src/js/utils/dataService.js',
  'src/js/utils/currency.js',
  'src/views/Profile.vue',
  'src/js/components/Vehicles.vue',
  'src/js/components/VehicleTimeline.vue',
  'package.json'
]

let failed = 0
for (const rel of required) {
  const p = resolve(root, rel)
  if (!existsSync(p)) {
    console.error('MISSING', rel)
    failed++
    continue
  }
  const text = readFileSync(p, 'utf8')
  if (rel.endsWith('.js') && text.includes('window.location.hash = \'#/login\'')) {
    console.error('BAD_401_REDIRECT', rel)
    failed++
  }
  console.log('OK', rel)
}

if (failed) {
  console.error(`Smoke failed: ${failed} issue(s)`)
  process.exit(1)
}
console.log('Smoke OK')

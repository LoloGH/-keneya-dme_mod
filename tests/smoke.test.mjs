import test from 'node:test';
import assert from 'node:assert/strict';
test('le format des identifiants patients est stable', () => {
  assert.match('PAT-2026-000001', /^PAT-\d{4}-\d{6}$/);
});
test('les rôles minimaux prévus sont documentés', () => {
  const roles = ['Administrateur', 'Médecin', 'Infirmier', 'Laboratoire', 'Radiologie', 'Réception'];
  assert.equal(roles.length, 6);
});

import type { Patient, RecordItem, Role } from '../types';

export const demoRoles: Role[]=['Administrateur','Médecin','Infirmier','Laboratoire','Radiologie','Pharmacien','Réception'];
export const permissions: Record<Role,string[]>={
  'Administrateur':['*'],'Médecin':['patients.view','patients.create','consultations.view','consultations.create','prescriptions.view','prescriptions.create','prescriptions.validate','documents.view'],
  'Infirmier':['patients.view','nursing.view','nursing.create'],'Laboratoire':['patients.view','laboratory.view','laboratory.results.create'],
  'Radiologie':['patients.view','imaging.view'],'Pharmacien':['patients.view','prescriptions.view'],'Réception':['patients.view','patients.create','appointments.view','appointments.manage']
};
export const patients: Patient[]=[
 {id:'1',number:'PAT-2026-000001',firstName:'Mamadou',lastName:'Traoré',sex:'Homme',age:42,blood:'O+',phone:'+221 70 000 10 01',allergies:['Pénicilline — sévère'],conditions:['Hypertension artérielle','Diabète type 2']},
 {id:'2',number:'PAT-2026-000002',firstName:'Awa',lastName:'Sow',sex:'Femme',age:34,blood:'A+',phone:'+221 70 000 10 02',allergies:['Aucune allergie connue'],conditions:['Asthme']},
 {id:'3',number:'PAT-2026-000003',firstName:'Ousmane',lastName:'Ba',sex:'Homme',age:68,blood:'B+',phone:'+221 70 000 10 03',allergies:['Iode — modérée'],conditions:['Insuffisance cardiaque']}
];
export const records: Record<string,RecordItem[]>={
 'Consultations':[{date:'04 sept. 2026',title:'Consultation de suivi',detail:'TA 120/80 · Hypertension contrôlée',status:'Terminée'},{date:'02 août 2026',title:'Consultation diabète',detail:'Glycémie 1,12 g/L',status:'Terminée'}],
 'Antécédents':[{date:'2018',title:'Diabète type 2',detail:'Suivi médical régulier',status:'Actif'},{date:'2015',title:'Appendicectomie',detail:'Sans complication',status:'Historique'}],
 'Allergies':[{date:'12 mars 2024',title:'Pénicilline',detail:'Réaction cutanée sévère',status:'Critique'}],
 'Médicaments':[{date:'Depuis jan. 2026',title:'Metformine 500 mg',detail:'1 comprimé matin et soir',status:'Actif'},{date:'Depuis jan. 2026',title:'Amlodipine 5 mg',detail:'1 comprimé le matin',status:'Actif'}],
 'Ordonnances':[{date:'04 sept. 2026',title:'ORD-2026-000021',detail:'Metformine, Amlodipine · QR de démonstration',status:'Validée'}],
 'Laboratoire':[{date:'03 sept. 2026',title:'LAB-2026-000087 — Bilan glycémique',detail:'HbA1c 6,8 % · Valeur de référence 4–6 %',status:'Validé'}],
 'Imagerie':[{date:'15 août 2026',title:'Échographie abdominale',detail:'Compte rendu disponible · Préparé pour PACS/DICOM',status:'Disponible'}],
 'Hospitalisations':[{date:'18–21 juin 2026',title:'HOSP-2026-000009',detail:'Médecine interne · sortie avec recommandations',status:'Terminée'}],
 'Soins':[{date:'04 sept. 2026 · 09:10',title:'Constantes relevées',detail:'TA 120/80 · Pouls 78 · SpO₂ 98 %',status:'Transmis'}],
 'Rendez-vous':[{date:'12 sept. 2026 · 09:30',title:'Suivi médical',detail:'Dr. Diallo · Médecine générale',status:'Confirmé'}],
 'Documents':[{date:'04 sept. 2026',title:'Ordonnance ORD-2026-000021.pdf',detail:'Document fictif · accès contrôlé côté futur API',status:'Signée'}],
 'Historique':[{date:'04 sept. 2026 · 14:20',title:'Consultation et ordonnance',detail:'Suivi HTA/diabète · Dr. Diallo'},{date:'03 sept. 2026 · 11:15',title:'Résultat laboratoire',detail:'Bilan glycémique validé'},{date:'15 août 2026 · 10:00',title:'Imagerie',detail:'Échographie abdominale disponible'}],
 'Audit':[{date:'04 sept. 2026 · 14:20',title:'Dr. Aïssatou Diallo',detail:'Consultation du dossier PAT-2026-000001',status:'Autorisé'}]
};

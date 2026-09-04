export type Role = 'Administrateur'|'Médecin'|'Infirmier'|'Laboratoire'|'Radiologie'|'Pharmacien'|'Réception';
export type Patient = {id:string; number:string; firstName:string; lastName:string; sex:'Homme'|'Femme'; age:number; blood:string; phone:string; allergies:string[]; conditions:string[]};
export type RecordItem = {date:string; title:string; detail:string; status?:string};
export type DemoUser = {name:string; role:Role; email:string};

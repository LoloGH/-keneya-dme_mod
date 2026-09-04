export type SmsStatus='En attente'|'Envoyé'|'Échec'|'Programmé';
export const smsService={send:async(to:string,body:string)=>({id:`SMS-${Date.now()}`,to,body,status:'Envoyé' as SmsStatus,simulated:true}),history:async()=>[{id:'SMS-2026-0041',to:'+221 70 000 10 01',body:'Keneya : votre rendez-vous est prévu le 12 septembre à 09h30.',status:'Programmé' as SmsStatus}]};

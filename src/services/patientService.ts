import { patients } from '../mocks/data';
import type { Patient } from '../types';
let data=[...patients];
export const patientService={list:async()=>data,get:async(id:string)=>data.find(p=>p.id===id),create:async(input:Omit<Patient,'id'|'number'>)=>{const p={...input,id:crypto.randomUUID(),number:`PAT-2026-${String(data.length+1).padStart(6,'0')}`};data=[p,...data];return p;}};

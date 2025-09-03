import { useEffect, useState } from 'react';
import api from '../../services/api';
import { useToast } from '../../contexts/ToastContext';
import { useConfirm } from '../../contexts/ConfirmContext';

export default function WardSettings() {
  const [list, setList] = useState([]);
  const [departments, setDepartments] = useState([]);
  const [form, setForm] = useState({ name: '', department_id: '' });
  const [msg, setMsg] = useState(null);
  const [editing, setEditing] = useState(null);
  const toast = useToast();
  const confirm = useConfirm();

  const load = async () => {
  try { const res = await api.get('/wards'); setList(res.data.data || res.data || []); }
  catch (e) { toast.error('Failed to load wards'); }
  };

  const loadDeps = async () => { try { const res = await api.get('/departments'); setDepartments(res.data.data || []); } catch (e) { /* ignore */ } };

  useEffect(()=>{ load(); }, []);
  useEffect(()=>{ loadDeps(); }, []);

  const create = async (e) => {
    e.preventDefault();
    if (!form.name) { setMsg({type:'error', text:'Name required'}); return; }
    try { await api.post('/wards', form); setForm({name:'', department_id:''}); load(); setMsg({type:'success', text:'Ward added'}); }
    catch (e) { setMsg({type:'error', text: e?.response?.data?.message || 'Failed'}); }
  };

  const saveEdit = async (e) => {
    e.preventDefault();
    if (!editing || !editing.id) return;
    try { await api.put(`/wards/${editing.id}`, editing); setEditing(null); load(); toast.success('Updated'); }
    catch (e) { toast.error('Update failed'); }
  };

  const remove = async (id) => { const ok = await confirm({title:'Delete', message:'Delete ward?'}); if (!ok) return; try { await api.delete(`/wards/${id}`); load(); toast.success('Deleted'); } catch (e) { toast.error('Delete failed'); } };

  return (
    <div className="space-y-4">
      <h2 className="font-medium">Wards</h2>
      {msg && <div className={`p-2 ${msg.type==='error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>{msg.text}</div>}
      <form onSubmit={create} className="grid grid-cols-1 md:grid-cols-3 gap-2">
        <input placeholder="Name" className="input" value={form.name} onChange={e=>setForm({...form, name:e.target.value})} />
        <select className="input" value={form.department_id} onChange={e=>setForm({...form, department_id:e.target.value})}>
          <option value="">-- Department (optional) --</option>
          {departments.map(d=> <option key={d.id} value={d.id}>{d.name}</option>)}
        </select>
        <div><button className="btn">Add</button></div>
      </form>

      {editing && (
        <form onSubmit={saveEdit} className="grid grid-cols-1 md:grid-cols-3 gap-2 mt-2">
          <input placeholder="Name" className="input" value={editing.name} onChange={e=>setEditing({...editing, name:e.target.value})} />
          <select className="input" value={editing.department_id} onChange={e=>setEditing({...editing, department_id:e.target.value})}>
            <option value="">-- Department (optional) --</option>
            {departments.map(d=> <option key={d.id} value={d.id}>{d.name}</option>)}
          </select>
          <div className="flex space-x-2"><button className="btn">Save</button><button type="button" className="btn" onClick={()=>setEditing(null)}>Cancel</button></div>
        </form>
      )}

      <div className="bg-card border border-border rounded p-2">
        <h3 className="font-semibold">Existing wards</h3>
        <ul className="list-disc list-inside mt-2">
          {list.map(w => (
            <li key={w.id} className="flex justify-between items-center py-1">
              <div>{w.name}</div>
              <div className="flex space-x-2"><button className="btn" onClick={() => setEditing(w)}>Edit</button><button className="btn" onClick={() => remove(w.id)}>Delete</button></div>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}

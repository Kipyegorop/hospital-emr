import { useEffect, useState } from 'react';
import api from '../../services/api';
import { useToast } from '../../contexts/ToastContext';
import { useConfirm } from '../../contexts/ConfirmContext';

export default function DepartmentSettings() {
  const [list, setList] = useState([]);
  const [form, setForm] = useState({ name: '', code: '' });
  const [editing, setEditing] = useState(null);
  const toast = useToast();
  const confirm = useConfirm();
  const [msg, setMsg] = useState(null);

  const load = async () => {
  try { const res = await api.get('/departments'); setList(res.data.data || res.data || []); }
  catch (e) { toast.error('Failed to load departments'); }
  };

  useEffect(()=>{ load(); }, []);

  const create = async (e) => {
    e.preventDefault();
    if (!form.name) { setMsg({type:'error', text:'Name required'}); return; }
  try { await api.post('/departments', form); setForm({name:'', code:''}); load(); toast.success('Department added'); }
  catch (e) { toast.error(e?.response?.data?.message || 'Failed'); }
  };

  const saveEdit = async (e) => {
    e.preventDefault();
    if (!editing || !editing.id) return;
    try { await api.put(`/departments/${editing.id}`, editing); setEditing(null); load(); setMsg({type:'success', text:'Updated'}); }
    catch (e) { setMsg({type:'error', text:'Update failed'}); }
  };

  const remove = async (id) => { const ok = await confirm({title:'Delete', message:'Delete department?'}); if (!ok) return; try { await api.delete(`/departments/${id}`); load(); toast.success('Deleted'); } catch (e) { toast.error('Delete failed'); } };

  return (
    <div className="space-y-4">
      <h2 className="font-medium">Departments</h2>
      {msg && <div className={`p-2 ${msg.type==='error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>{msg.text}</div>}
      <form onSubmit={create} className="grid grid-cols-1 md:grid-cols-3 gap-2">
        <input placeholder="Name" className="input" value={form.name} onChange={e=>setForm({...form, name:e.target.value})} />
        <input placeholder="Code" className="input" value={form.code} onChange={e=>setForm({...form, code:e.target.value})} />
        <div><button className="btn">Add</button></div>
      </form>

      {editing && (
        <form onSubmit={saveEdit} className="grid grid-cols-1 md:grid-cols-3 gap-2 mt-2">
          <input placeholder="Name" className="input" value={editing.name} onChange={e=>setEditing({...editing, name:e.target.value})} />
          <input placeholder="Code" className="input" value={editing.code} onChange={e=>setEditing({...editing, code:e.target.value})} />
          <div className="flex space-x-2"><button className="btn">Save</button><button type="button" className="btn" onClick={()=>setEditing(null)}>Cancel</button></div>
        </form>
      )}

      <div className="bg-card border border-border rounded p-2">
        <h3 className="font-semibold">Existing departments</h3>
        <ul className="list-disc list-inside mt-2">
          {list.map(d => (
            <li key={d.id} className="flex justify-between items-center py-1">
              <div>{d.name} {d.code ? `(${d.code})` : ''}</div>
              <div className="flex space-x-2"><button className="btn" onClick={() => setEditing(d)}>Edit</button><button className="btn" onClick={() => remove(d.id)}>Delete</button></div>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}

import { useEffect, useState } from 'react';
import api from '../../services/api';
import { useToast } from '../../contexts/ToastContext';
import { useConfirm } from '../../contexts/ConfirmContext';

export default function MedicationSettings() {
  const [list, setList] = useState([]);
  const [editing, setEditing] = useState(null);
  const [loading, setLoading] = useState(false);
  const [form, setForm] = useState({ name: '', generic_name: '', unit: '' });
  const [msg, setMsg] = useState(null);
  const toast = useToast();
  const confirm = useConfirm();

  const load = async () => {
    setLoading(true);
    try {
      const res = await api.get('/medications');
      setList(res.data.data || res.data || []);
    } catch (e) { toast.error('Failed to load medications'); }
    finally { setLoading(false); }
  };

  useEffect(() => { load(); }, []);

  const create = async (e) => {
    e.preventDefault();
    if (!form.name) { toast.error('Name required'); return; }
    try {
      await api.post('/medications', form);
      setForm({ name:'', generic_name:'', unit:'' });
      load();
      toast.success('Medication added');
    } catch (e) { setMsg({type:'error', text: e?.response?.data?.message || 'Failed'}); }
  };

  const saveEdit = async (e) => {
    e.preventDefault();
    if (!editing || !editing.id) return;
    try { await api.put(`/medications/${editing.id}`, editing); setEditing(null); load(); setMsg({type:'success', text:'Updated'}); }
    catch (e) { setMsg({type:'error', text:'Update failed'}); }
  };

  const remove = async (id) => {
    const ok = await confirm({ title: 'Delete', message: 'Delete medication?' });
    if (!ok) return;
    try { await api.delete(`/medications/${id}`); load(); toast.success('Deleted'); }
    catch (e) { toast.error('Delete failed'); }
  };

  return (
    <div className="space-y-4">
      <h2 className="font-medium">Medications</h2>
  {/* local msg retained for backwards compatibility but toasts are primary */}
  <form onSubmit={create} className="grid grid-cols-1 md:grid-cols-4 gap-2">
        <input placeholder="Name" className="input" value={form.name} onChange={e=>setForm({...form, name:e.target.value})} />
        <input placeholder="Generic name" className="input" value={form.generic_name} onChange={e=>setForm({...form, generic_name:e.target.value})} />
        <input placeholder="Unit (e.g. mg)" className="input" value={form.unit} onChange={e=>setForm({...form, unit:e.target.value})} />
        <div><button className="btn">Add</button></div>
      </form>

      {editing && (
        <form onSubmit={saveEdit} className="grid grid-cols-1 md:grid-cols-4 gap-2 mt-2">
          <input placeholder="Name" className="input" value={editing.name} onChange={e=>setEditing({...editing, name:e.target.value})} />
          <input placeholder="Generic name" className="input" value={editing.generic_name} onChange={e=>setEditing({...editing, generic_name:e.target.value})} />
          <input placeholder="Unit (e.g. mg)" className="input" value={editing.unit} onChange={e=>setEditing({...editing, unit:e.target.value})} />
          <div className="flex space-x-2"><button className="btn">Save</button><button type="button" className="btn" onClick={()=>setEditing(null)}>Cancel</button></div>
        </form>
      )}

      <div className="bg-card border border-border rounded p-2">
        <h3 className="font-semibold">Existing</h3>
        {loading ? <div>Loading...</div> : (
          <ul className="list-disc list-inside mt-2">
            {list.map(m => (
              <li key={m.id} className="flex justify-between items-center py-1">
                <div>{m.name} {m.generic_name ? `(${m.generic_name})` : ''}</div>
                <div className="flex items-center space-x-2">
                  <button className="btn" onClick={() => setEditing(m)}>Edit</button>
                  <button className="btn" onClick={() => remove(m.id)}>Delete</button>
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}

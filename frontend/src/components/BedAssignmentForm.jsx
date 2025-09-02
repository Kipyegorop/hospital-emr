import { useState } from 'react';
import axios from 'axios';

const BedAssignmentForm = ({ bedId }) => {
  const [patientId, setPatientId] = useState('');
  const [message, setMessage] = useState(null);

  const handleAssign = async () => {
    try {
      const res = await axios.post(`/api/beds/${bedId}/assign`, { patient_id: patientId });
      setMessage({ type: 'success', text: 'Patient assigned to bed' });
    } catch (err) {
      setMessage({ type: 'error', text: err.response?.data?.message || 'Failed' });
    }
  };

  const handleVacate = async () => {
    try {
      const res = await axios.post(`/api/beds/${bedId}/vacate`);
      setMessage({ type: 'success', text: 'Bed vacated' });
    } catch (err) {
      setMessage({ type: 'error', text: err.response?.data?.message || 'Failed' });
    }
  };

  return (
    <div className="space-y-2">
      {message && <div className={`p-2 rounded ${message.type === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>{message.text}</div>}
      <input className="input" placeholder="Patient ID" value={patientId} onChange={(e) => setPatientId(e.target.value)} />
      <div className="flex space-x-2">
        <button className="btn btn-primary" onClick={handleAssign}>Assign</button>
        <button className="btn btn-outline" onClick={handleVacate}>Vacate</button>
      </div>
    </div>
  );
};

export default BedAssignmentForm;

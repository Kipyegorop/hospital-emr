import React from 'react';
import PatientRegistrationForm from '../components/PatientRegistrationForm';
import { useNavigate } from 'react-router-dom';

export default function RegisterPatient() {
  const navigate = useNavigate();

  const handleSuccess = (data) => {
    // data should include created patient id
    const id = data?.data?.id || data?.id;
    // if caller set ?inpatient=1 redirect to ipd admit
    const inpatient = new URLSearchParams(window.location.search).get('inpatient');
    if (inpatient && id) {
      navigate(`/ipd?patient_id=${id}`);
      return;
    }
    console.log('Patient created', data);
  };

  return (
    <div className="p-6">
      <h1 className="text-2xl mb-4">Register Patient</h1>
      <PatientRegistrationForm onSuccess={handleSuccess} />
    </div>
  );
}

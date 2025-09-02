import React from 'react';
import PatientRegistrationForm from '../components/PatientRegistrationForm';

export default function RegisterPatient() {
  return (
    <div className="p-6">
      <h1 className="text-2xl mb-4">Register Patient</h1>
      <PatientRegistrationForm onSuccess={(data) => console.log('Patient created', data)} />
    </div>
  );
}

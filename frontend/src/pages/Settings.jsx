import { useState } from 'react';
import MedicationSettings from '../components/settings/MedicationSettings';
import DoctorSettings from '../components/settings/DoctorSettings';
import DepartmentSettings from '../components/settings/DepartmentSettings';
import WardSettings from '../components/settings/WardSettings';

const tabs = [
  { key: 'medications', label: 'Medications' },
  { key: 'doctors', label: 'Doctors' },
  { key: 'departments', label: 'Departments' },
  { key: 'wards', label: 'Wards' },
];

export default function Settings() {
  const [active, setActive] = useState('medications');

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-4">Settings</h1>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <nav className="md:col-span-1 bg-card border border-border rounded p-4">
          <ul className="space-y-2">
            {tabs.map(t => (
              <li key={t.key}>
                <button
                  onClick={() => setActive(t.key)}
                  className={`w-full text-left px-3 py-2 rounded ${active===t.key ? 'bg-primary text-white' : 'hover:bg-muted'}`}>
                  {t.label}
                </button>
              </li>
            ))}
          </ul>
        </nav>

        <section className="md:col-span-3">
          {active === 'medications' && <MedicationSettings />}
          {active === 'doctors' && <DoctorSettings />}
          {active === 'departments' && <DepartmentSettings />}
          {active === 'wards' && <WardSettings />}
        </section>
      </div>
    </div>
  );
}

import { useState } from 'react';
import { Plus, Calendar, Clock, User, Stethoscope } from 'lucide-react';

const Appointments = () => {
  const [appointments] = useState([
    {
      id: 1,
      patient: 'John Doe',
      doctor: 'Dr. Kamau',
      date: '2025-01-25',
      time: '09:00',
      type: 'Consultation',
      status: 'Scheduled'
    },
    {
      id: 2,
      patient: 'Mary Wanjiku',
      doctor: 'Dr. Wanjiku',
      date: '2025-01-25',
      time: '10:30',
      type: 'Follow-up',
      status: 'Confirmed'
    }
  ]);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-foreground">Appointments</h1>
          <p className="text-muted-foreground">Manage patient appointments and scheduling</p>
        </div>
        <button className="btn btn-primary">
          <Plus className="h-4 w-4 mr-2" />
          New Appointment
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {appointments.map((appointment) => (
          <div key={appointment.id} className="card">
            <div className="card-header">
              <div className="flex items-center space-x-2">
                <Calendar className="h-5 w-5 text-primary" />
                <span className="text-sm text-muted-foreground">{appointment.date}</span>
              </div>
              <div className="flex items-center space-x-2">
                <Clock className="h-4 w-4 text-muted-foreground" />
                <span className="text-sm">{appointment.time}</span>
              </div>
            </div>
            <div className="card-content">
              <h3 className="font-medium mb-2">{appointment.patient}</h3>
              <div className="flex items-center space-x-2 text-sm text-muted-foreground mb-2">
                <Stethoscope className="h-4 w-4" />
                <span>{appointment.doctor}</span>
              </div>
              <div className="flex items-center space-x-2 text-sm text-muted-foreground mb-3">
                <User className="h-4 w-4" />
                <span>{appointment.type}</span>
              </div>
              <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                appointment.status === 'Scheduled' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'
              }`}>
                {appointment.status}
              </span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default Appointments;

import { useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { 
  Home,
  Users,
  Calendar,
  Stethoscope,
  Pill,
  Building2,
  TestTube,
  FileText,
  Receipt,
  CreditCard,
  BarChart3,
  User,
  Settings
} from 'lucide-react';

const Sidebar = ({ isOpen, onClose, currentPath }) => {
  const navigate = useNavigate();
  const { user } = useAuth();

  const navigation = [
    {
      name: 'Dashboard',
      href: '/dashboard',
      icon: Home,
      roles: ['*'], // All roles
    },
    {
      name: 'Patients',
      href: '/patients',
      icon: Users,
      roles: ['*'],
    },
    {
      name: 'Register Patient',
      href: '/register-patient',
      icon: User,
      roles: ['*'],
    },
    {
      name: 'Appointments',
      href: '/appointments',
      icon: Calendar,
      roles: ['*'],
    },
    {
      name: 'Consultations',
      href: '/consultations',
      icon: Stethoscope,
      roles: ['doctor', 'nurse', 'admin', 'super_admin'],
    },
    {
      name: 'Prescriptions',
      href: '/prescriptions',
      icon: Pill,
      roles: ['doctor', 'pharmacist', 'admin', 'super_admin'],
    },
    {
      name: 'Wards',
      href: '/wards',
      icon: Building2,
      roles: ['nurse', 'admin', 'super_admin'],
    },
    {
      name: 'Lab Tests',
      href: '/lab-tests',
      icon: TestTube,
      roles: ['doctor', 'lab_tech', 'admin', 'super_admin'],
    },
    {
      name: 'Medications',
      href: '/medications',
      icon: FileText,
      roles: ['pharmacist', 'admin', 'super_admin'],
    },
    {
      name: 'Bills',
      href: '/bills',
      icon: Receipt,
      roles: ['admin', 'super_admin', 'receptionist'],
    },
    {
      name: 'NHIF Claims',
      href: '/nhif-claims',
      icon: CreditCard,
      roles: ['admin', 'super_admin'],
    },
    {
      name: 'Users',
      href: '/users',
      icon: Users,
      roles: ['admin', 'super_admin'],
    },
    {
      name: 'Reports',
      href: '/reports',
      icon: BarChart3,
      roles: ['admin', 'super_admin'],
    },
    {
      name: 'Settings',
      href: '/settings',
      icon: Settings,
      roles: ['admin', 'super_admin'],
    },
  ];

  const canAccess = (item) => {
    if (item.roles.includes('*')) return true;
    return item.roles.includes(user?.role?.name);
  };

  const filteredNavigation = navigation.filter(canAccess);

  const handleNavigation = (href) => {
    navigate(href);
    onClose();
  };

  return (
    <>
      {/* Desktop sidebar */}
      <div className="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 lg:z-50">
        <div className="flex-1 flex flex-col min-h-0 bg-card border-r border-border">
          {/* Logo */}
          <div className="flex items-center h-16 flex-shrink-0 px-4 bg-primary">
            <h1 className="text-xl font-bold text-primary-foreground">
              Smart Hospital
            </h1>
          </div>

          {/* Navigation */}
          <nav className="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
            {filteredNavigation.map((item) => {
              const isActive = currentPath === item.href;
              return (
                <button
                  key={item.name}
                  onClick={() => handleNavigation(item.href)}
                  className={`${
                    isActive
                      ? 'bg-primary text-primary-foreground'
                      : 'text-foreground hover:bg-accent hover:text-accent-foreground'
                  } group flex items-center px-2 py-2 text-sm font-medium rounded-md w-full transition-colors`}
                >
                  <item.icon
                    className={`${
                      isActive ? 'text-primary-foreground' : 'text-muted-foreground'
                    } mr-3 flex-shrink-0 h-5 w-5 transition-colors`}
                  />
                  {item.name}
                </button>
              );
            })}
          </nav>
        </div>
      </div>

      {/* Mobile sidebar */}
      <div
        className={`${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        } lg:hidden fixed inset-y-0 left-0 z-50 w-64 bg-card border-r border-border transform transition-transform duration-300 ease-in-out`}
      >
        <div className="flex-1 flex flex-col min-h-0">
          {/* Mobile header */}
          <div className="flex items-center justify-between h-16 flex-shrink-0 px-4 bg-primary">
            <h1 className="text-xl font-bold text-primary-foreground">
              Smart Hospital
            </h1>
            <button
              onClick={onClose}
              className="text-primary-foreground hover:text-primary-foreground/80"
            >
              <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          {/* Mobile navigation */}
          <nav className="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
            {filteredNavigation.map((item) => {
              const isActive = currentPath === item.href;
              return (
                <button
                  key={item.name}
                  onClick={() => handleNavigation(item.href)}
                  className={`${
                    isActive
                      ? 'bg-primary text-primary-foreground'
                      : 'text-foreground hover:bg-accent hover:text-accent-foreground'
                  } group flex items-center px-2 py-2 text-sm font-medium rounded-md w-full transition-colors`}
                >
                  <item.icon
                    className={`${
                      isActive ? 'text-primary-foreground' : 'text-muted-foreground'
                    } mr-3 flex-shrink-0 h-5 w-5 transition-colors`}
                  />
                  {item.name}
                </button>
              );
            })}
          </nav>
        </div>
      </div>

      {/* Spacer for desktop */}
      <div className="hidden lg:block lg:w-64"></div>
    </>
  );
};

export default Sidebar;

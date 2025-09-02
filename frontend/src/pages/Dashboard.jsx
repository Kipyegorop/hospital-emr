import { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { 
  Users, 
  Calendar, 
  Stethoscope, 
  Pill, 
  Building2, 
  TestTube, 
  Receipt, 
  CreditCard,
  TrendingUp,
  TrendingDown,
  Activity,
  BarChart3
} from 'lucide-react';

const Dashboard = () => {
  const { user } = useAuth();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Simulate loading dashboard data
    const loadDashboardData = async () => {
      try {
        // In a real app, this would be an API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Mock data for demonstration
        setStats({
          today: {
            appointments: 24,
            consultations: 18,
            new_patients: 5,
            prescriptions: 12,
            lab_tests: 8,
            bills: 15,
          },
          this_month: {
            appointments: 456,
            consultations: 342,
            new_patients: 89,
            prescriptions: 234,
            lab_tests: 156,
            bills: 298,
            revenue: 1250000,
          },
          total: {
            patients: 1247,
            users: 45,
            departments: 12,
            wards: 8,
            beds: 156,
            medications: 234,
          },
          pending: {
            appointments: 12,
            consultations: 8,
            prescriptions: 15,
            lab_tests: 6,
            bills: 23,
            nhif_claims: 18,
          },
        });
      } catch (error) {
        console.error('Error loading dashboard data:', error);
      } finally {
        setLoading(false);
      }
    };

    loadDashboardData();
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    );
  }

  const StatCard = ({ title, value, icon: Icon, change, changeType = 'neutral' }) => (
    <div className="bg-card border border-border rounded-lg p-6">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-muted-foreground">{title}</p>
          <p className="text-2xl font-bold text-foreground">{value}</p>
          {change && (
            <div className={`flex items-center mt-2 text-sm ${
              changeType === 'positive' ? 'text-success' : 
              changeType === 'negative' ? 'text-destructive' : 
              'text-muted-foreground'
            }`}>
              {changeType === 'positive' ? (
                <TrendingUp className="h-4 w-4 mr-1" />
              ) : changeType === 'negative' ? (
                <TrendingDown className="h-4 w-4 mr-1" />
              ) : (
                <Activity className="h-4 w-4 mr-1" />
              )}
              {change}
            </div>
          )}
        </div>
        <div className="p-3 bg-primary/10 rounded-lg">
          <Icon className="h-6 w-6 text-primary" />
        </div>
      </div>
    </div>
  );

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-foreground">Dashboard</h1>
          <p className="text-muted-foreground">
            Welcome back, {user?.name}. Here's what's happening today.
          </p>
        </div>
        <div className="text-sm text-muted-foreground">
          {new Date().toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
          })}
        </div>
      </div>

      {/* Today's Overview */}
      <div>
        <h2 className="text-xl font-semibold text-foreground mb-4">Today's Overview</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
          <StatCard
            title="Appointments"
            value={stats?.today?.appointments || 0}
            icon={Calendar}
            change="+12% from yesterday"
            changeType="positive"
          />
          <StatCard
            title="Consultations"
            value={stats?.today?.consultations || 0}
            icon={Stethoscope}
            change="+8% from yesterday"
            changeType="positive"
          />
          <StatCard
            title="New Patients"
            value={stats?.today?.new_patients || 0}
            icon={Users}
            change="+2 from yesterday"
            changeType="positive"
          />
          <StatCard
            title="Prescriptions"
            value={stats?.today?.prescriptions || 0}
            icon={Pill}
            change="+15% from yesterday"
            changeType="positive"
          />
          <StatCard
            title="Lab Tests"
            value={stats?.today?.lab_tests || 0}
            icon={TestTube}
            change="+5% from yesterday"
            changeType="positive"
          />
          <StatCard
            title="Bills"
            value={stats?.today?.bills || 0}
            icon={Receipt}
            change="+18% from yesterday"
            changeType="positive"
          />
        </div>
      </div>

      {/* Monthly Statistics */}
      <div>
        <h2 className="text-xl font-semibold text-foreground mb-4">This Month</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard
            title="Total Appointments"
            value={stats?.this_month?.appointments || 0}
            icon={Calendar}
          />
          <StatCard
            title="Total Consultations"
            value={stats?.this_month?.consultations || 0}
            icon={Stethoscope}
          />
          <StatCard
            title="New Patients"
            value={stats?.this_month?.new_patients || 0}
            icon={Users}
          />
          <StatCard
            title="Revenue (KES)"
            value={`${(stats?.this_month?.revenue || 0).toLocaleString()}`}
            icon={Receipt}
            change="+12% from last month"
            changeType="positive"
          />
        </div>
      </div>

      {/* System Overview */}
      <div>
        <h2 className="text-xl font-semibold text-foreground mb-4">System Overview</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <StatCard
            title="Total Patients"
            value={stats?.total?.patients || 0}
            icon={Users}
          />
          <StatCard
            title="Active Users"
            value={stats?.total?.users || 0}
            icon={Users}
          />
          <StatCard
            title="Departments"
            value={stats?.total?.departments || 0}
            icon={Building2}
          />
          <StatCard
            title="Wards"
            value={stats?.total?.wards || 0}
            icon={Building2}
          />
          <StatCard
            title="Available Beds"
            value={stats?.total?.beds || 0}
            icon={Building2}
          />
          <StatCard
            title="Medications"
            value={stats?.total?.medications || 0}
            icon={Pill}
          />
        </div>
      </div>

      {/* Pending Items */}
      <div>
        <h2 className="text-xl font-semibold text-foreground mb-4">Pending Items</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <StatCard
            title="Pending Appointments"
            value={stats?.pending?.appointments || 0}
            icon={Calendar}
            change="Requires attention"
            changeType="negative"
          />
          <StatCard
            title="Pending Consultations"
            value={stats?.pending?.consultations || 0}
            icon={Stethoscope}
            change="Requires attention"
            changeType="negative"
          />
          <StatCard
            title="Pending Prescriptions"
            value={stats?.pending?.prescriptions || 0}
            icon={Pill}
            change="Requires attention"
            changeType="negative"
          />
          <StatCard
            title="Pending Lab Tests"
            value={stats?.pending?.lab_tests || 0}
            icon={TestTube}
            change="Requires attention"
            changeType="negative"
          />
          <StatCard
            title="Pending Bills"
            value={stats?.pending?.bills || 0}
            icon={Receipt}
            change="Requires attention"
            changeType="negative"
          />
          <StatCard
            title="Pending NHIF Claims"
            value={stats?.pending?.nhif_claims || 0}
            icon={CreditCard}
            change="Requires attention"
            changeType="negative"
          />
        </div>
      </div>

      {/* Quick Actions */}
      <div>
        <h2 className="text-xl font-semibold text-foreground mb-4">Quick Actions</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <button className="p-4 bg-card border border-border rounded-lg hover:bg-accent hover:text-accent-foreground transition-colors text-left">
            <Users className="h-8 w-8 text-primary mb-2" />
            <h3 className="font-medium">Add Patient</h3>
            <p className="text-sm text-muted-foreground">Register new patient</p>
          </button>
          <button className="p-4 bg-card border border-border rounded-lg hover:bg-accent hover:text-accent-foreground transition-colors text-left">
            <Calendar className="h-8 w-8 text-primary mb-2" />
            <h3 className="font-medium">Book Appointment</h3>
            <p className="text-sm text-muted-foreground">Schedule consultation</p>
          </button>
          <button className="p-4 bg-card border border-border rounded-lg hover:bg-accent hover:text-accent-foreground transition-colors text-left">
            <Receipt className="h-8 w-8 text-primary mb-2" />
            <h3 className="font-medium">Create Bill</h3>
            <p className="text-sm text-muted-foreground">Generate invoice</p>
          </button>
          <button className="p-4 bg-card border border-border rounded-lg hover:bg-accent hover:text-accent-foreground transition-colors text-left">
            <BarChart3 className="h-8 w-8 text-primary mb-2" />
            <h3 className="font-medium">View Reports</h3>
            <p className="text-sm text-muted-foreground">Analytics & insights</p>
          </button>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;

import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ThemeProvider } from './contexts/ThemeContext';
import { AuthProvider } from './contexts/AuthContext';
import Layout from './components/Layout';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Patients from './pages/Patients';
import RegisterPatient from './pages/RegisterPatient';
import Appointments from './pages/Appointments';
import Consultations from './pages/Consultations';
import Prescriptions from './pages/Prescriptions';
import Wards from './pages/Wards';
import LabTests from './pages/LabTests';
import Medications from './pages/Medications';
import Exceptions from './pages/Exceptions';
import SalesReport from './pages/SalesReport';
import StockHistory from './pages/StockHistory';
import Bills from './pages/Bills';
import BillDetail from './pages/BillDetail';
import CreateBill from './pages/CreateBill';
import NhifClaims from './pages/NhifClaims';
import Users from './pages/Users';
import Reports from './pages/Reports';
import ProtectedRoute from './components/ProtectedRoute';

// Create a client
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <ThemeProvider>
        <AuthProvider>
          <Router>
            <div className="min-h-screen bg-background">
              <Routes>
                {/* Public routes */}
                <Route path="/login" element={<Login />} />
                
                {/* Protected routes */}
                <Route path="/" element={
                  <ProtectedRoute>
                    <Layout />
                  </ProtectedRoute>
                }>
                  <Route index element={<Navigate to="/dashboard" replace />} />
                  <Route path="dashboard" element={<Dashboard />} />
                  <Route path="patients" element={<Patients />} />
                  <Route path="register-patient" element={<RegisterPatient />} />
                  <Route path="appointments" element={<Appointments />} />
                  <Route path="consultations" element={<Consultations />} />
                  <Route path="prescriptions" element={<Prescriptions />} />
                  <Route path="wards" element={<Wards />} />
                  <Route path="lab-tests" element={<LabTests />} />
                  <Route path="medications" element={<Medications />} />
                  <Route path="pharmacy/exceptions" element={<Exceptions />} />
                  <Route path="pharmacy/sales" element={<SalesReport />} />
                  <Route path="pharmacy/stock-history" element={<StockHistory />} />
                  <Route path="bills" element={<Bills />} />
                  <Route path="bills/create" element={<CreateBill />} />
                  <Route path="bills/:id" element={<BillDetail />} />
                  <Route path="nhif-claims" element={<NhifClaims />} />
                  <Route path="users" element={<Users />} />
                  <Route path="reports" element={<Reports />} />
                </Route>
                
                {/* Catch all route */}
                <Route path="*" element={<Navigate to="/dashboard" replace />} />
              </Routes>
            </div>
          </Router>
        </AuthProvider>
      </ThemeProvider>
    </QueryClientProvider>
  );
}

export default App;

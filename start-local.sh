#!/bin/bash

echo "🏥 Starting Smart Hospital EMR System (Local Desktop Mode)"
echo "=================================================="

# Check if required software is installed
echo "🔍 Checking requirements..."

if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP 8.1+"
    exit 1
fi

if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer"
    exit 1
fi

if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js 18+"
    exit 1
fi

if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm"
    exit 1
fi

echo "✅ All requirements are met!"
echo ""

# Start Laravel Backend
echo "🚀 Starting Laravel Backend..."
cd backend

# Check if .env exists, if not create it
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
    php artisan key:generate
fi

# Run migrations and seeders
echo "🗄️ Setting up database..."
php artisan migrate:fresh --seed

# Start Laravel server in background
echo "🌐 Starting backend server on http://localhost:8000"
php artisan serve --port=8000 > ../backend.log 2>&1 &
BACKEND_PID=$!
echo "✅ Backend started (PID: $BACKEND_PID)"

cd ..

# Start React Frontend
echo "🎨 Starting React Frontend..."
cd frontend

# Install dependencies if needed
if [ ! -d "node_modules" ]; then
    echo "📦 Installing frontend dependencies..."
    npm install
fi

# Start Vite server in background
echo "🌐 Starting frontend server on http://localhost:5173"
npm run dev > ../frontend.log 2>&1 &
FRONTEND_PID=$!
echo "✅ Frontend started (PID: $FRONTEND_PID)"

cd ..

# Wait for servers to start
echo ""
echo "⏳ Waiting for servers to start..."
sleep 5

echo ""
echo "🎉 Smart Hospital EMR System is now running locally!"
echo "=================================================="
echo ""
echo "📍 Access Points:"
echo "   Frontend: http://localhost:5173"
echo "   Backend:  http://localhost:8000"
echo "   API:      http://localhost:8000/api"
echo ""
echo "🔑 Login Credentials:"
echo "   Email:    admin@smarthospital.com"
echo "   Password: password"
echo ""
echo "📋 To stop the system:"
echo "   ./stop-local.sh"
echo "   or manually: kill $BACKEND_PID $FRONTEND_PID"
echo ""
echo "📊 Logs:"
echo "   Backend:  backend.log"
echo "   Frontend: frontend.log"
echo ""
echo "💡 Tips:"
echo "   - Keep this terminal open to see logs"
echo "   - Use Ctrl+C to stop this script"
echo "   - The system will continue running in background"
echo "   - Access your hospital system at http://localhost:5173"

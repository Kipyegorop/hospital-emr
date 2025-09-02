#!/bin/bash

echo "🚀 Starting Smart Hospital EMR System..."

# Start Laravel Backend
echo "📡 Starting Laravel Backend..."
cd backend
php artisan serve > ../backend.log 2>&1 &
BACKEND_PID=$!
echo "✅ Backend started (PID: $BACKEND_PID)"

# Start React Frontend
echo "🌐 Starting React Frontend..."
cd ../frontend
npm run dev > ../frontend.log 2>&1 &
FRONTEND_PID=$!
echo "✅ Frontend started (PID: $FRONTEND_PID)"

# Wait a moment for servers to start
sleep 3

echo ""
echo "🎉 Smart Hospital EMR System is running!"
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
echo "   kill $BACKEND_PID $FRONTEND_PID"
echo ""
echo "📊 Logs:"
echo "   Backend:  backend.log"
echo "   Frontend: frontend.log"

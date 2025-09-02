#!/bin/bash

echo "🛑 Stopping Smart Hospital EMR System..."

# Stop Laravel backend
echo "📡 Stopping Laravel Backend..."
pkill -f "php artisan serve"
echo "✅ Backend stopped"

# Stop React frontend
echo "🌐 Stopping React Frontend..."
pkill -f "vite"
echo "✅ Frontend stopped"

# Stop any other related processes
echo "🧹 Cleaning up processes..."
pkill -f "smart_hospital"

echo ""
echo "🎉 All services stopped successfully!"
echo "💡 To start again, run: ./start-local.sh"

import React from 'react';
import { useToast } from '../contexts/ToastContext';

export default function ToastContainer() {
  const { toasts, remove } = useToast();

  return (
    <div className="fixed right-4 top-4 z-50 space-y-2">
      {toasts.map(t => (
        <div key={t.id} className={`p-3 rounded shadow ${t.type==='error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>
          <div className="flex items-center justify-between space-x-2">
            <div className="text-sm">{t.message}</div>
            <button className="ml-2 text-xs" onClick={() => remove(t.id)}>X</button>
          </div>
        </div>
      ))}
    </div>
  );
}

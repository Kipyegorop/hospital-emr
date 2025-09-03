import React, { createContext, useContext, useState, useCallback } from 'react';

const ToastContext = createContext(null);

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);

  const push = useCallback((toast) => {
    const id = Date.now() + Math.random();
    setToasts((t) => [...t, { id, ...toast }]);
    if (toast.duration !== 0) {
      setTimeout(() => setToasts((t) => t.filter(x => x.id !== id)), toast.duration || 4000);
    }
    return id;
  }, []);

  const remove = useCallback((id) => setToasts((t) => t.filter(x => x.id !== id)), []);

  return (
    <ToastContext.Provider value={{ push, remove, toasts }}>
      {children}
    </ToastContext.Provider>
  );
}

export function useToast() {
  const ctx = useContext(ToastContext);
  if (!ctx) throw new Error('useToast must be used within ToastProvider');
  return {
    success: (msg, opts) => ctx.push({ type: 'success', message: msg, ...opts }),
    error: (msg, opts) => ctx.push({ type: 'error', message: msg, ...opts }),
    info: (msg, opts) => ctx.push({ type: 'info', message: msg, ...opts }),
    raw: (t) => ctx.push(t),
    toasts: ctx.toasts,
    remove: ctx.remove,
  };
}

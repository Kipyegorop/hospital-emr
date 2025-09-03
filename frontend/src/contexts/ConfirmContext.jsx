import React, { createContext, useContext, useState } from 'react';

const ConfirmContext = createContext(null);

export function ConfirmProvider({ children }) {
  const [pending, setPending] = useState(null);

  const confirm = (options) => new Promise((resolve) => {
    setPending({ ...options, resolve });
  });

  const handle = (result) => {
    if (pending?.resolve) pending.resolve(result);
    setPending(null);
  };

  return (
    <ConfirmContext.Provider value={{ confirm }}>
      {children}
      {pending && (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
          <div className="bg-card border border-border rounded p-4 max-w-md w-full">
            <h4 className="font-semibold">{pending.title || 'Confirm'}</h4>
            <p className="text-sm mt-2">{pending.message || 'Are you sure?'}</p>
            <div className="mt-4 flex justify-end space-x-2">
              <button className="btn" onClick={()=>handle(false)}>{pending.cancelText || 'Cancel'}</button>
              <button className="btn btn-primary" onClick={()=>handle(true)}>{pending.confirmText || 'OK'}</button>
            </div>
          </div>
        </div>
      )}
    </ConfirmContext.Provider>
  );
}

export function useConfirm() {
  const ctx = useContext(ConfirmContext);
  if (!ctx) throw new Error('useConfirm must be used within ConfirmProvider');
  return ctx.confirm;
}

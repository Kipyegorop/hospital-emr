import React, { useEffect, useState, useRef } from 'react';
import { useParams } from 'react-router-dom';
import billingService from '../services/billing';
import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';

const BillDetail = () => {
  const { id } = useParams();
  const [bill, setBill] = useState(null);
  const receiptRef = useRef();

  useEffect(()=>{
    billingService.get(id).then(res=> setBill(res.data));
  },[id]);

  if (!bill) return <div className="p-8">Loading...</div>;

  return (
    <div className="p-8">
      <div className="flex justify-between items-center mb-4">
        <h1 className="text-2xl font-bold">Bill {bill.bill_number}</h1>
        <div className="space-x-2">
          <button onClick={() => window.print()} className="px-3 py-1 bg-gray-200 rounded">Print</button>
          <button onClick={async () => {
            // generate PDF
            const element = receiptRef.current;
            const canvas = await html2canvas(element, { scale: 2 });
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save(`receipt-${bill.bill_number}.pdf`);
          }} className="px-3 py-1 bg-blue-600 text-white rounded">Download PDF</button>
        </div>
      </div>

      <div ref={receiptRef} className="bg-white p-6 rounded shadow max-w-2xl">
        <div className="flex justify-between mb-6">
          <div>
            <h2 className="font-semibold">Hospital EMR</h2>
            <div className="text-sm text-gray-600">Receipt</div>
          </div>
          <div className="text-sm text-right">
            <div>Bill: {bill.bill_number}</div>
            <div>Date: {new Date(bill.bill_date || bill.created_at).toLocaleString()}</div>
          </div>
        </div>

        <div className="mb-4">
          <div className="font-medium">Patient</div>
          <div>{bill.patient?.full_name || ''} ({bill.patient?.patient_number || bill.patient_id})</div>
        </div>

        <div className="mb-4">
          <table className="w-full text-sm">
            <thead>
              <tr className="text-left">
                <th>Description</th>
                <th className="text-right">Qty</th>
                <th className="text-right">Unit</th>
                <th className="text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              {(bill.billable_items || []).map((it, idx) => (
                <tr key={idx}>
                  <td>{it.name || it.description || it.type}</td>
                  <td className="text-right">{it.quantity || ''}</td>
                  <td className="text-right">{it.unit_price || ''}</td>
                  <td className="text-right">{it.total || ''}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="flex justify-end">
          <div className="w-64">
            <div className="flex justify-between"><div>Subtotal</div><div>{bill.subtotal}</div></div>
            <div className="flex justify-between"><div>Tax</div><div>{bill.tax_amount}</div></div>
            <div className="flex justify-between"><div>Discount</div><div>{bill.discount_amount}</div></div>
            <div className="flex justify-between font-semibold"><div>Total</div><div>{bill.total_amount}</div></div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default BillDetail;

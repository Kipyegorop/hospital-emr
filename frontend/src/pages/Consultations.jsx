import React from 'react';
import ConsultationForm from '../components/ConsultationForm';

const Consultations = () => {
	return (
		<div className="space-y-6">
			<div className="flex items-center justify-between">
				<div>
					<h1 className="text-3xl font-bold text-foreground">Consultations</h1>
					<p className="text-muted-foreground">Record clinical notes and SOAP entries</p>
				</div>
			</div>

			<div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
				<div className="lg:col-span-2">
					<div className="bg-card border border-border rounded-lg p-4">
						<p className="text-sm text-muted-foreground">Consultation list and history will appear here.</p>
					</div>
				</div>
				<div>
					<div className="bg-card border border-border rounded-lg p-4">
						<h2 className="font-semibold mb-2">Start Consultation</h2>
						<ConsultationForm />
					</div>
				</div>
			</div>
		</div>
	);
};

export default Consultations;

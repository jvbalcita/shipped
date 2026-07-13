export * from './auth';
export * from './navigation';
export * from './ui';

export interface CloudConnectionSummary {
    status: string;
    last_validated_at: string | null;
    environment_count: number;
}

export interface ConnectedEnvironmentSummary {
    id: number;
    application_id: string;
    application_name: string;
    environment_id: string;
    environment_name: string;
    domains: string[];
    synced_at: string | null;
}

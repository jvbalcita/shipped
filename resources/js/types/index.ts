export * from './auth';
export * from './creator';
export * from './navigation';
export * from './report';
export * from './technology';
export * from './ui';

export type VerificationStatus = 'verified' | 'failed' | 'stale' | 'unverified';

export interface ProjectVerification {
    laravel_cloud_url: string | null;
    verification_status: VerificationStatus;
    verification_failure_reason: string | null;
    verified_at: string | null;
    verification_checked_at: string | null;
}

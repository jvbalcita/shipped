export const contentReportReasons = [
    { value: 'broken_link', label: 'Broken or dead link' },
    { value: 'not_laravel', label: 'Not actually built with Laravel' },
    { value: 'misleading', label: 'Misleading content' },
    { value: 'spam', label: 'Spam' },
    { value: 'inappropriate', label: 'Inappropriate content' },
    { value: 'duplicate', label: 'Duplicate project' },
    { value: 'ownership', label: 'Ownership or attribution dispute' },
    { value: 'other', label: 'Something else' },
] as const;

export type ContentReportReasonValue =
    (typeof contentReportReasons)[number]['value'];

export type ContentReportableType = 'project' | 'comment' | 'review';

export interface ReportedSubject {
    type: ContentReportableType;
    title: string;
    excerpt: string | null;
    url: string | null;
    author_username: string | null;
    context: string | null;
    live: boolean;
}

export interface ContentReportRow {
    id: number;
    reason: ContentReportReasonValue;
    reason_label: string;
    note: string | null;
    created_at: string | null;
    reporter_username: string | null;
    subject: ReportedSubject;
}

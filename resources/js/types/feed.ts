export type FeedActivity = {
    id: number;
    verb: 'launched' | 'released' | 'reviewed' | 'cheered' | 'verified';
    occurred_at: string;
    actor: { name: string; username: string } | null;
    project: {
        name: string;
        slug: string;
        creator_username: string | null;
    } | null;
    meta: Record<string, unknown> | null;
};

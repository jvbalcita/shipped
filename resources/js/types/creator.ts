import type { CardTechnology } from './technology';
import type { ProjectVerification } from './index';

export type CreatorLink = {
    type: string;
    url: string;
};

export type CreatorStats = {
    public_projects: number;
    verified_projects: number;
    ship_stories: number;
    releases: number;
};

export type CreatorProfile = {
    id: number;
    name: string;
    username: string;
    title: string | null;
    location: string | null;
    bio: string | null;
    avatar_path: string | null;
    avatar_url: string | null;
    links: CreatorLink[] | null;
    followers_count: number;
    followed_by_viewer: boolean;
    stats: CreatorStats;
};

export type CreatorProject = {
    id: number;
    name: string;
    slug: string;
    tagline: string | null;
    cover_image_url: string | null;
    logo_url: string | null;
    pricing: string | null;
    pricing_label: string | null;
    launch_date: string | null;
    verification_status: string;
    filed_serial: string | null;
    cheers_count: number;
    rating_average: number | null;
    ship_story_excerpt: string | null;
    creator: {
        id: number;
        name: string;
        username: string;
    } | null;
    category: {
        id: number;
        name: string;
        slug: string;
    } | null;
    tags: Array<{
        id: number;
        name: string;
        slug: string;
    }>;
    technologies?: CardTechnology[];
};

export type ProjectCardData = CreatorProject & {
    creator: NonNullable<CreatorProject['creator']>;
    category: NonNullable<CreatorProject['category']>;
    cheered_by_viewer?: boolean;
};

export type ShippingHistoryEntry = {
    project: ProjectCardData;
    latest_release: {
        id: number;
        title: string | null;
        notes: string | null;
        published_at: string | null;
    } | null;
    release_count: number;
    ship_story_excerpt: string | null;
};

export type ProfileProject = {
    id: number;
    name: string;
    tagline: string | null;
    category: {
        id: number;
        name: string;
    } | null;
    profile_featured_order: number | null;
    is_discoverable: boolean;
    published_releases_count: number;
};

export type ProjectShipStory = {
    id: number;
    problem: string | null;
    audience: string | null;
    shipped: string | null;
    build_decisions: string | null;
    hardest_problem: string | null;
    lessons_learned: string | null;
    next: string | null;
    is_complete: boolean;
    is_approved: boolean;
    approved_at: string | null;
};

export type StudioRelease = {
    id: number;
    title: string;
    notes: string;
    published_at: string | null;
};

export type StudioProjectScreenshot = {
    id: number;
    url: string;
    caption: string | null;
};

export type StudioProject = {
    id: number;
    name: string;
    slug: string;
    tagline: string | null;
    description: string | null;
    category_id: number | null;
    live_url: string | null;
    github_url: string | null;
    pricing: string | null;
    launch_date: string | null;
    is_public: boolean;
    cover_image_url: string | null;
    logo_url: string | null;
    tags: Array<{ id: number; name: string }>;
    screenshots: StudioProjectScreenshot[];
    releases: StudioRelease[];
} & ProjectVerification;

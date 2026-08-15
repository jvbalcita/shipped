import { project as coverProject } from '@/routes/cover';

/**
 * Default typographic cover shown when a project has no uploaded cover image.
 * Rendered server-side as a "launch plate" that carries the project's own name,
 * so the fallback reads as an intentional record, not a missing image.
 */
export function defaultCoverUrl(project: {
    creator: { username: string };
    slug: string;
}): string {
    return coverProject.url({ creator: project.creator, project });
}

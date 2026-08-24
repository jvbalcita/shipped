export const profileLinkOptions = [
    {
        value: 'website',
        label: 'Website',
        prefix: 'https://',
        placeholder: 'https://your-site.com',
        description: 'Your personal site, portfolio, or homepage.',
    },
    {
        value: 'github',
        label: 'GitHub',
        prefix: 'https://github.com/',
        placeholder: 'https://github.com/your-username',
        description: 'Your code, open-source work, and repositories.',
    },
    {
        value: 'x',
        label: 'X',
        prefix: 'https://x.com/',
        placeholder: 'https://x.com/your-handle',
        description: 'Your public posts and short-form updates.',
    },
    {
        value: 'linkedin',
        label: 'LinkedIn',
        prefix: 'https://www.linkedin.com/in/',
        placeholder: 'https://www.linkedin.com/in/your-slug',
        description: 'Your professional profile and work history.',
    },
    {
        value: 'devto',
        label: 'Dev.to',
        prefix: 'https://dev.to/',
        placeholder: 'https://dev.to/your-username',
        description: 'Your developer articles and community profile.',
    },
    {
        value: 'hashnode',
        label: 'Hashnode',
        prefix: 'https://hashnode.com/@',
        placeholder: 'https://hashnode.com/@your-username',
        description: 'Your technical blog and developer profile.',
    },
    {
        value: 'stackoverflow',
        label: 'Stack Overflow',
        prefix: 'https://stackoverflow.com/users/',
        placeholder: 'https://stackoverflow.com/users/123/your-name',
        description: 'Your answers, reputation, and developer profile.',
    },
    {
        value: 'npm',
        label: 'npm',
        prefix: 'https://www.npmjs.com/~',
        placeholder: 'https://www.npmjs.com/~your-username',
        description: 'Your published packages and JavaScript work.',
    },
    {
        value: 'bluesky',
        label: 'Bluesky',
        prefix: 'https://bsky.app/profile/',
        placeholder: 'https://bsky.app/profile/your-handle',
        description: 'Your public posts and developer community presence.',
    },
    {
        value: 'codepen',
        label: 'CodePen',
        prefix: 'https://codepen.io/',
        placeholder: 'https://codepen.io/your-username',
        description: 'Your front-end experiments and creative coding.',
    },
] as const;

export type ProfileLinkType = (typeof profileLinkOptions)[number]['value'];

export type StoredProfileLinkType = ProfileLinkType | 'twitter';

export function profileLinkOption(type: string) {
    return (
        profileLinkOptions.find((option) => option.value === type) ??
        profileLinkOptions[0]
    );
}

export function normalizeProfileLinkType(type: string): ProfileLinkType {
    if (type === 'twitter') {
        return 'x';
    }

    return profileLinkOptions.some((option) => option.value === type)
        ? (type as ProfileLinkType)
        : 'website';
}

export function normalizeProfileLinkUrl(type: string, url: string): string {
    if (type === 'twitter') {
        return url.replace(
            /^https:\/\/(?:www\.)?twitter\.com\//i,
            'https://x.com/',
        );
    }

    return url;
}

export function profileLinkLabel(type: string): string {
    if (type === 'twitter') {
        return 'X';
    }

    return profileLinkOption(type).label;
}

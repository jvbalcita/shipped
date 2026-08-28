export type TechnologyOption = {
    id: number;
    name: string;
    slug: string;
};

export type TechnologyGroupOption = {
    group: string;
    label: string;
    multiple: boolean;
    searchable: boolean;
    suggested: string[];
    technologies: TechnologyOption[];
};

export type ProjectTechnology = {
    name: string;
    slug: string;
    group: string;
    group_label: string;
    provenance: string;
    provenance_label: string;
    observed_at: string | null;
};

export type CardTechnology = {
    id: number;
    name: string;
    slug: string;
};

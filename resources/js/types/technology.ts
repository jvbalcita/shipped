export type TechnologyOption = {
    id: number;
    name: string;
    slug: string;
};

export type TechnologyGroupOption = {
    group: string;
    label: string;
    multiple: boolean;
    technologies: TechnologyOption[];
};

export type ProjectTechnology = {
    name: string;
    slug: string;
    group: string;
    group_label: string;
    provenance: string;
    provenance_label: string;
};

export type CardTechnology = {
    id: number;
    name: string;
    slug: string;
};

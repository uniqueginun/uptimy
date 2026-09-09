export type CheckStatus = 'up' | 'down' | 'error';

export type Endpoint = {
    location: string;
    frequency: number;
    last_check: string | null;
    last_status: number | null;
    last_check_status: CheckStatus | null;
    uptime: number | null;
    uptime_24h: number | null;
};

export type Site = {
    id: number;
    url: string;
    domain: string | null;
    created_at: string;
    updated_at: string;
    endpoints?: {
        data: Endpoint[];
    };
};

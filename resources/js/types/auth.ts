export type UserRole = 'superadmin' | 'admin' | 'customer';

export type User = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    role: UserRole;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    assigned_properties?: Array<{ id: number; name: string; slug: string }>;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

export type City = {
    id: number;
    name: string;
    slug: string;
    province: string;
    properties_count?: number;
};

export type Facility = {
    id: number;
    name: string;
    icon: string | null;
    properties_count?: number;
};

export type PropertyType = 'hotel' | 'kos';
export type PropertyStatus = 'draft' | 'published';

export type PropertyImage = {
    id: number;
    property_id: number;
    path: string;
    alt: string | null;
    sort_order: number;
    url: string | null;
};

export type BookingPeriod = 'daily' | 'monthly' | 'yearly';
export type PaymentPlan = 'full' | 'per_period' | 'installment';

export type RoomTypePrice = {
    id: number;
    room_type_id: number;
    period: BookingPeriod;
    price: number;
    min_duration: number;
    max_duration: number;
    is_active: boolean;
};

export type RoomStatus = 'available' | 'maintenance';

export type Room = {
    id: number;
    property_id: number;
    room_type_id: number;
    name: string;
    status: RoomStatus;
    room_type?: RoomType;
    active_bookings_count?: number;
};

export type RoomType = {
    id: number;
    property_id: number;
    name: string;
    description: string | null;
    capacity: number;
    prices?: RoomTypePrice[];
    rooms?: Room[];
    rooms_count?: number;
};

export type Property = {
    id: number;
    city_id: number;
    created_by: number | null;
    type: PropertyType;
    name: string;
    slug: string;
    description: string | null;
    address: string;
    latitude: number | null;
    longitude: number | null;
    whatsapp_number: string | null;
    status: PropertyStatus;
    thumbnail_path: string | null;
    thumbnail_alt: string | null;
    thumbnail_url: string | null;
    rating_avg: number;
    reviews_count: number;
    accent: string;
    whatsapp_url: string | null;
    city?: City;
    images?: PropertyImage[];
    facilities?: Facility[];
    room_types?: RoomType[];
    rooms?: Room[];
    reviews?: Review[];
    admins?: import('./auth').User[];
    room_types_count?: number;
    rooms_count?: number;
    created_at: string;
    updated_at: string;
};

export type BankAccount = {
    id: number;
    property_id: number | null;
    bank_name: string;
    account_number: string;
    account_holder: string;
    is_active: boolean;
    property?: Pick<Property, 'id' | 'name'> | null;
};

export type BookingStatus =
    | 'pending_payment'
    | 'confirmed'
    | 'completed'
    | 'cancelled'
    | 'expired';

export type InvoiceStatus = 'unpaid' | 'partial' | 'paid' | 'cancelled';

export type PaymentMethod = 'manual_transfer' | 'gateway';

export type PaymentStatus =
    | 'pending'
    | 'waiting_verification'
    | 'paid'
    | 'rejected'
    | 'expired'
    | 'failed';

export type RefundStatus =
    | 'awaiting_account'
    | 'requested'
    | 'completed'
    | 'rejected';

export type RefundInitiator = 'customer' | 'admin';

export type Payment = {
    id: number;
    invoice_id: number;
    method: PaymentMethod;
    status: PaymentStatus;
    amount: number;
    bank_account_id: number | null;
    proof_path: string | null;
    proof_url: string | null;
    paid_at: string | null;
    verified_by: number | null;
    verified_at: string | null;
    rejection_reason: string | null;
    created_at: string;
    invoice?: Invoice;
    bank_account?: BankAccount | null;
    verifier?: import('./auth').User | null;
};

export type Invoice = {
    id: number;
    booking_id: number;
    number: string;
    amount: number;
    late_fee: number;
    paid_amount: number;
    total_due: number;
    remaining: number;
    due_date: string;
    grace_until: string;
    late_fee_applied_at: string | null;
    upload_reset_at: string | null;
    allow_partial: boolean;
    status: InvoiceStatus;
    payments?: Payment[];
    booking?: Booking;
    created_at: string;
};

export type Refund = {
    id: number;
    booking_id: number;
    requested_by: number | null;
    initiated_by: RefundInitiator;
    amount: number;
    status: RefundStatus;
    bank_name: string | null;
    account_number: string | null;
    account_holder: string | null;
    proof_path: string | null;
    proof_url: string | null;
    processed_by: number | null;
    processed_at: string | null;
    rejection_reason: string | null;
    note: string | null;
    created_at: string;
    booking?: Booking;
    requester?: import('./auth').User | null;
    processor?: import('./auth').User | null;
};

export type Review = {
    id: number;
    property_id: number;
    user_id: number;
    booking_id: number;
    rating: number;
    comment: string | null;
    is_visible: boolean;
    created_at: string;
    property?: Pick<Property, 'id' | 'name' | 'slug'>;
    user?: import('./auth').User;
    booking?: Pick<Booking, 'id' | 'code'>;
};

export type StatusHistory = {
    id: number;
    historable_id: number;
    historable_type: string;
    from_status: string | null;
    to_status: string;
    actor_id: number | null;
    note: string | null;
    created_at: string;
    actor?: import('./auth').User | null;
};

export type Booking = {
    id: number;
    code: string;
    property_id: number;
    room_type_id: number;
    room_id: number;
    user_id: number;
    extended_from_id: number | null;
    period: BookingPeriod;
    duration: number;
    payment_plan: PaymentPlan;
    unit_price: number;
    total_price: number;
    total_paid: number;
    guests: number;
    check_in: string;
    check_out: string;
    status: BookingStatus;
    expires_at: string | null;
    confirmed_at: string | null;
    completed_at: string | null;
    cancelled_at: string | null;
    cancelled_by: number | null;
    cancel_reason: string | null;
    terms_accepted_at: string | null;
    terms_version: number;
    note: string | null;
    created_at: string;
    property: Property;
    room_type: RoomType;
    room: Room;
    user?: import('./auth').User;
    invoices?: Invoice[];
    refund?: Refund | null;
    review?: Review | null;
    extensions?: Booking[];
    parent?: Booking | null;
    status_histories?: StatusHistory[];
};

export type Page = {
    id: number;
    slug: string;
    title: string;
    body: string;
    version: number;
    updated_at: string;
};

export type BookingRules = {
    minDownPaymentPercent: number;
    lateGraceDays: number;
    lateFee: number;
    refundWindowDays: number;
    paymentExpiryHours: number;
    maxRejectedUploads: number;
    extensionOpenDaysBeforeEnd: number;
    maxGalleryImages: number;
    termsVersion: number;
    invoiceReminderDays: number;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

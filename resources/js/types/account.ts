export interface Account {
    id: number;
    name: string;
    emp_id: string;
    email: string;
    profile_photo_path: string;
    role: string;
    sub_role: string | null;
    /**
     * Hierarki sub-role user dari sub-role-nya ke puncak organisasi
     * (sub_role -> induk -> dst.), urut dari yang terdekat. Kosong untuk
     * user tanpa sub-role. Datanya dinamis (`sub_roles.parent_id`), jadi
     * rantai bisa berubah tanpa mengubah kode.
     */
    sub_role_hierarchy?: { id: number; name: string; code: string }[];
}
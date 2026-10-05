import type { MenuItem } from '@/types/menu'

// DUMMY — bentuknya sama persis dgn tabel `menus`.
// `route_name` = route utama yang diwakili menu, `active_routes` = route
// turunan (create/show) yang tetap menyalakan menu yang sama.
export const dummyMenus: MenuItem[] = [
  { id: 1, name: 'Dashboard', key: 'dashboard', icon: 'LayoutDashboard', url: '/dashboard', route_name: 'dashboard', active_routes: null, description: 'Main dashboard', is_active: true, parent_id: null },
  { id: 2, name: 'Profile', key: 'profile', icon: 'User', url: '/profile', route_name: null, active_routes: null, description: null, is_active: true, parent_id: null },
  { id: 3, name: 'Notifications', key: 'notifications', icon: 'Bell', url: '/notifications', route_name: null, active_routes: null, description: null, is_active: true, parent_id: null },
  { id: 4, name: 'Messages', key: 'messages', icon: 'Mail', url: '/messages', route_name: null, active_routes: null, description: null, is_active: true, parent_id: null },
  { id: 5, name: 'HR', key: 'hr', icon: 'Users', url: '/hr', route_name: null, active_routes: null, description: 'Human resources', is_active: true, parent_id: null },
  { id: 6, name: 'My Requests', key: 'my-requests', icon: 'FileText', url: '/hr/requests', route_name: null, active_routes: null, description: null, is_active: true, parent_id: 5 },
  { id: 7, name: 'Leaves', key: 'leaves', icon: 'CalendarOff', url: '/hr/leaves', route_name: null, active_routes: null, description: null, is_active: true, parent_id: 5 },
  { id: 8, name: 'Payroll', key: 'payroll', icon: 'Wallet', url: '/hr/payroll', route_name: null, active_routes: null, description: null, is_active: true, parent_id: 5 },
  { id: 9, name: 'Time Entry', key: 'time-entry', icon: 'Clock', url: '/hr/time-entry', route_name: null, active_routes: null, description: null, is_active: true, parent_id: 5 },
  { id: 10, name: 'Performance', key: 'performance', icon: 'TrendingUp', url: '/performance', route_name: null, active_routes: null, description: null, is_active: true, parent_id: null },
  { id: 11, name: 'Company', key: 'company', icon: 'Building2', url: '/company', route_name: null, active_routes: null, description: null, is_active: true, parent_id: null },
  { id: 12, name: 'Benefits', key: 'benefits', icon: 'Star', url: '/benefits', route_name: null, active_routes: null, description: null, is_active: true, parent_id: null },
  { id: 13, name: 'Legacy Module', key: 'legacy', icon: 'Archive', url: '/legacy', route_name: null, active_routes: null, description: 'Deprecated', is_active: false, parent_id: null },
]
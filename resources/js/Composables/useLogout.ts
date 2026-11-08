import { router } from '@inertiajs/vue3';

import { clearAdminSearchSession } from '@/Features/Admin/AdminSearch/searchSession';

export function useLogout() {
  const logout = () => {
    clearAdminSearchSession();
    router.post(route('logout'), {}, {
      onSuccess: () => {
        window.location.href = route('login');
      },
      onError: () => {
        console.error('Logout failed.');
      },
    });
  };

  const logoutMicrosoft = () => {
    clearAdminSearchSession();
    router.post(route('logout.microsoft'));
  };

  return { logout, logoutMicrosoft };
}

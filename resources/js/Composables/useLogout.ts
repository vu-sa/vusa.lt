import { router } from '@inertiajs/vue3';

export function useLogout() {
  const logout = () => {
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
    router.post(route('logout.microsoft'));
  };

  return { logout, logoutMicrosoft };
}

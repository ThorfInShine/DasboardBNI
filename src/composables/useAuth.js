import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';

const user = ref(null);
const token = ref(null);

export function useAuth() {
  const router = useRouter();

  const isAuthenticated = computed(() => !!token.value);
  const isAdmin = computed(() => user.value?.role === 'admin');
  const isUser = computed(() => user.value?.role === 'user');
  const userRole = computed(() => user.value?.role);

  const initAuth = () => {
    const storedToken = localStorage.getItem('user_token');
    const storedUser = localStorage.getItem('user_data');

    if (storedToken) {
      token.value = storedToken;
    }

    if (storedUser) {
      try {
        user.value = JSON.parse(storedUser);
      } catch (e) {
        console.error('Failed to parse user data:', e);
        logout();
      }
    }
  };

  const login = (userData, userToken) => {
    token.value = userToken;
    user.value = userData;

    localStorage.setItem('user_token', userToken);
    localStorage.setItem('user_data', JSON.stringify(userData));
  };

  const logout = () => {
    token.value = null;
    user.value = null;

    localStorage.removeItem('user_token');
    localStorage.removeItem('user_data');

    router.push('/auth/login');
  };

  const updateUser = (userData) => {
    user.value = { ...user.value, ...userData };
    localStorage.setItem('user_data', JSON.stringify(user.value));
  };

  const hasRole = (role) => {
    return user.value?.role === role;
  };

  const canAccess = (requiredRole) => {
    if (!requiredRole) return true;
    if (requiredRole === 'admin') return isAdmin.value;
    if (requiredRole === 'user') return isUser.value || isAdmin.value;
    return false;
  };

  return {
    user: computed(() => user.value),
    token: computed(() => token.value),
    isAuthenticated,
    isAdmin,
    isUser,
    userRole,
    initAuth,
    login,
    logout,
    updateUser,
    hasRole,
    canAccess
  };
}

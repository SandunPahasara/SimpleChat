import React, { useState, useEffect } from 'react';
import { StyleSheet, View, Text, ActivityIndicator } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';

// API & WS Services
import api, { setAuthToken, getAuthToken } from './src/services/api';
import { initEcho, disconnectEcho } from './src/services/echo';

// Screens
import LoginScreen from './src/screens/LoginScreen';
import RegisterScreen from './src/screens/RegisterScreen';
import ConversationsScreen from './src/screens/ConversationsScreen';
import ChatRoomScreen from './src/screens/ChatRoomScreen';
import CreateGroupScreen from './src/screens/CreateGroupScreen';

const Stack = createStackNavigator();

export default function App() {
  const [token, setToken] = useState(null);
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isDarkMode, setIsDarkMode] = useState(true); // Default to Dark Mode as requested

  // Load session or check authentication on start
  useEffect(() => {
    // If we had a persisted token (like in AsyncStorage), we would retrieve it here.
    // Since we want robust execution and avoid crash on missing AsyncStorage, we start fresh.
    setLoading(false);
  }, []);

  const toggleTheme = () => {
    setIsDarkMode((prev) => !prev);
  };

  const handleLoginSuccess = async (newToken, loggedInUser) => {
    setLoading(true);
    try {
      setToken(newToken);
      setUser(loggedInUser);
      
      // Configure Axios Authorization Header
      setAuthToken(newToken);
      
      // Initialize real-time WebSockets (Laravel Echo)
      initEcho(newToken);
    } catch (err) {
      console.log('Post-login setup failed:', err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    setLoading(true);
    try {
      // Notify backend to delete token
      await api.post('/logout');
    } catch (err) {
      console.log('Logout API error:', err.message);
    } finally {
      // Disconnect Websocket Echo
      disconnectEcho();
      
      // Clear local states
      setAuthToken(null);
      setToken(null);
      setUser(null);
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <View style={[styles.center, { backgroundColor: isDarkMode ? '#09090B' : '#F9FAFB' }]}>
        <ActivityIndicator size="large" color="#FF2D20" />
        <Text style={[styles.loadingText, { color: isDarkMode ? '#F4F4F5' : '#111827' }]}>
          Loading SimpleChat...
        </Text>
      </View>
    );
  }

  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        {token === null ? (
          // Unauthenticated Stack
          <>
            <Stack.Screen
              name="Login"
              component={LoginScreen}
              initialParams={{
                isDarkMode,
                toggleTheme,
                onLoginSuccess: handleLoginSuccess,
              }}
            />
            <Stack.Screen
              name="Register"
              component={RegisterScreen}
              initialParams={{
                isDarkMode,
                toggleTheme,
                onLoginSuccess: handleLoginSuccess,
              }}
            />
          </>
        ) : (
          // Authenticated Stack
          <>
            <Stack.Screen
              name="Conversations"
              component={ConversationsScreen}
              initialParams={{
                isDarkMode,
                toggleTheme,
                onLogout: handleLogout,
                currentUser: user,
              }}
            />
            <Stack.Screen
              name="ChatRoom"
              component={ChatRoomScreen}
              initialParams={{
                currentUserId: user ? user.id : null,
              }}
            />
            <Stack.Screen
              name="CreateGroup"
              component={CreateGroupScreen}
            />
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}

const styles = StyleSheet.create({
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 14,
    fontSize: 14,
    fontWeight: 'bold',
  },
});

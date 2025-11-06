# Frontend Integration Guide - Laravel API

## Overview

This guide helps you migrate your Next.js frontend from Firebase to Laravel API backend.

## Table of Contents

- [Setup](#setup)
- [API Client Configuration](#api-client-configuration)
- [Authentication Migration](#authentication-migration)
- [Data Fetching Migration](#data-fetching-migration)
- [File Upload Migration](#file-upload-migration)
- [Environment Variables](#environment-variables)

---

## Setup

### 1. Install Dependencies

```bash
npm install axios
# or
yarn add axios
```

### 2. Environment Variables

Create/Update `.env.local`:

```env
# Laravel API Configuration
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_API_TIMEOUT=30000

# Admin Panel (separate)
NEXT_PUBLIC_ADMIN_API_URL=http://localhost:8000/admin/api

# File Storage
NEXT_PUBLIC_STORAGE_URL=http://localhost:8000/storage
```

---

## API Client Configuration

### Create API Client

**File:** `src/lib/api/client.ts`

```typescript
import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from "axios";

// API Response Types
export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  error?: {
    code: string;
    message: string;
    details?: any;
  };
  message?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

class ApiClient {
  private client: AxiosInstance;

  constructor(baseURL: string = process.env.NEXT_PUBLIC_API_URL!) {
    this.client = axios.create({
      baseURL,
      timeout: parseInt(process.env.NEXT_PUBLIC_API_TIMEOUT || "30000"),
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
    });

    // Request Interceptor
    this.client.interceptors.request.use(
      (config) => {
        // Add auth token
        const token = this.getToken();
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => {
        return Promise.reject(error);
      }
    );

    // Response Interceptor
    this.client.interceptors.response.use(
      (response) => response,
      async (error) => {
        const originalRequest = error.config;

        // Handle 401 - Token expired
        if (error.response?.status === 401 && !originalRequest._retry) {
          originalRequest._retry = true;

          try {
            const newToken = await this.refreshToken();
            originalRequest.headers.Authorization = `Bearer ${newToken}`;
            return this.client(originalRequest);
          } catch (refreshError) {
            // Refresh failed, redirect to login
            this.logout();
            window.location.href = "/login";
            return Promise.reject(refreshError);
          }
        }

        return Promise.reject(error);
      }
    );
  }

  // Token Management
  private getToken(): string | null {
    if (typeof window === "undefined") return null;
    return localStorage.getItem("auth_token");
  }

  private setToken(token: string): void {
    if (typeof window === "undefined") return;
    localStorage.setItem("auth_token", token);
  }

  private removeToken(): void {
    if (typeof window === "undefined") return;
    localStorage.removeItem("auth_token");
  }

  private async refreshToken(): Promise<string> {
    const response = await this.client.post<ApiResponse<{ token: string }>>(
      "/auth/refresh"
    );
    const newToken = response.data.data?.token;
    if (newToken) {
      this.setToken(newToken);
      return newToken;
    }
    throw new Error("Failed to refresh token");
  }

  private logout(): void {
    this.removeToken();
  }

  // HTTP Methods
  async get<T = any>(
    url: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    const response = await this.client.get<ApiResponse<T>>(url, config);
    return response.data;
  }

  async post<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    const response = await this.client.post<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  async put<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    const response = await this.client.put<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  async delete<T = any>(
    url: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    const response = await this.client.delete<ApiResponse<T>>(url, config);
    return response.data;
  }

  async upload<T = any>(
    url: string,
    file: File,
    additionalData?: Record<string, any>
  ): Promise<ApiResponse<T>> {
    const formData = new FormData();
    formData.append("file", file);

    if (additionalData) {
      Object.entries(additionalData).forEach(([key, value]) => {
        formData.append(key, value);
      });
    }

    const response = await this.client.post<ApiResponse<T>>(url, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });

    return response.data;
  }

  // Auth Methods
  async login(
    email: string,
    password: string
  ): Promise<
    ApiResponse<{
      token: string;
      token_type: string;
      expires_in: number;
      user: any;
    }>
  > {
    const response = await this.post("/auth/login", { email, password });

    if (response.success && response.data?.token) {
      this.setToken(response.data.token);
    }

    return response;
  }

  async logout(): Promise<void> {
    try {
      await this.post("/auth/logout");
    } finally {
      this.removeToken();
    }
  }

  async getCurrentUser(): Promise<ApiResponse<any>> {
    return this.get("/auth/me");
  }
}

// Export singleton instance
export const apiClient = new ApiClient();

// Export admin client
export const adminApiClient = new ApiClient(
  process.env.NEXT_PUBLIC_ADMIN_API_URL!
);
```

---

## Authentication Migration

### Replace Firebase Auth with API Auth

**Before (Firebase):**

```typescript
// src/app/login/page.tsx
import { signInWithEmailAndPassword } from "firebase/auth";
import { useAuth } from "@/firebase";

const auth = useAuth();
await signInWithEmailAndPassword(auth, email, password);
```

**After (Laravel API):**

```typescript
// src/app/login/page.tsx
import { apiClient } from "@/lib/api/client";

const response = await apiClient.login(email, password);

if (response.success) {
  // Token is automatically stored
  // Redirect to dashboard
  router.push("/dashboard");
} else {
  // Show error
  toast({
    variant: "destructive",
    title: "Login Failed",
    description: response.error?.message,
  });
}
```

### Update Auth Context

**File:** `src/contexts/auth-context.tsx`

```typescript
"use client";

import React, { createContext, useContext, useEffect, useState } from "react";
import { apiClient } from "@/lib/api/client";
import type { User } from "@/lib/types";

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Check if user is authenticated on mount
    fetchCurrentUser();
  }, []);

  const fetchCurrentUser = async () => {
    try {
      const response = await apiClient.getCurrentUser();

      if (response.success && response.data) {
        setUser(response.data);
      }
    } catch (error) {
      console.error("Failed to fetch user:", error);
      setUser(null);
    } finally {
      setIsLoading(false);
    }
  };

  const login = async (email: string, password: string) => {
    const response = await apiClient.login(email, password);

    if (!response.success) {
      throw new Error(response.error?.message || "Login failed");
    }

    setUser(response.data!.user);
  };

  const logout = async () => {
    await apiClient.logout();
    setUser(null);
  };

  const refreshUser = async () => {
    await fetchCurrentUser();
  };

  return (
    <AuthContext.Provider
      value={{ user, isLoading, login, logout, refreshUser }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
}
```

---

## Data Fetching Migration

### Create API Service Layer

**File:** `src/lib/api/services/candidates.service.ts`

```typescript
import { apiClient, ApiResponse, PaginatedResponse } from "../client";
import type { Candidate, EnrichedCandidate } from "@/lib/types";

export interface CandidateQueryParams {
  status?: string;
  job_role_id?: number;
  search?: string;
  page?: number;
  per_page?: number;
}

export const candidatesService = {
  // Get all candidates
  getAll: async (
    params?: CandidateQueryParams
  ): Promise<ApiResponse<PaginatedResponse<EnrichedCandidate>>> => {
    return apiClient.get("/candidates", { params });
  },

  // Get single candidate
  getById: async (id: string): Promise<ApiResponse<EnrichedCandidate>> => {
    return apiClient.get(`/candidates/${id}`);
  },

  // Create candidate
  create: async (data: Partial<Candidate>): Promise<ApiResponse<Candidate>> => {
    return apiClient.post("/candidates", data);
  },

  // Update candidate
  update: async (
    id: string,
    data: Partial<Candidate>
  ): Promise<ApiResponse<Candidate>> => {
    return apiClient.put(`/candidates/${id}`, data);
  },

  // Delete candidate
  delete: async (id: string): Promise<ApiResponse<void>> => {
    return apiClient.delete(`/candidates/${id}`);
  },

  // Get compliance documents
  getCompliance: async (candidateId: string): Promise<ApiResponse<any[]>> => {
    return apiClient.get(`/candidates/${candidateId}/compliance`);
  },

  // Upload compliance document
  uploadCompliance: async (
    candidateId: string,
    complianceId: string,
    file: File,
    expiryDate?: string
  ): Promise<ApiResponse<any>> => {
    return apiClient.upload(
      `/candidates/${candidateId}/compliance/${complianceId}/upload`,
      file,
      expiryDate ? { expiry_date: expiryDate } : undefined
    );
  },
};
```

### Replace Firestore Hooks with React Query

**Install React Query:**

```bash
npm install @tanstack/react-query
```

**Setup Query Client:**

**File:** `src/lib/query-client.ts`

```typescript
import { QueryClient } from "@tanstack/react-query";

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});
```

**Add Provider:**

**File:** `src/app/layout.tsx`

```typescript
"use client";

import { QueryClientProvider } from "@tanstack/react-query";
import { queryClient } from "@/lib/query-client";
import { AuthProvider } from "@/contexts/auth-context";

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body>
        <QueryClientProvider client={queryClient}>
          <AuthProvider>{children}</AuthProvider>
        </QueryClientProvider>
      </body>
    </html>
  );
}
```

**Create Custom Hook:**

**File:** `src/hooks/use-candidates.ts`

```typescript
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import {
  candidatesService,
  CandidateQueryParams,
} from "@/lib/api/services/candidates.service";
import { useToast } from "./use-toast";

export function useCandidates(params?: CandidateQueryParams) {
  return useQuery({
    queryKey: ["candidates", params],
    queryFn: async () => {
      const response = await candidatesService.getAll(params);
      if (!response.success) {
        throw new Error(response.error?.message);
      }
      return response.data!;
    },
  });
}

export function useCandidate(id: string) {
  return useQuery({
    queryKey: ["candidates", id],
    queryFn: async () => {
      const response = await candidatesService.getById(id);
      if (!response.success) {
        throw new Error(response.error?.message);
      }
      return response.data!;
    },
    enabled: !!id,
  });
}

export function useCreateCandidate() {
  const queryClient = useQueryClient();
  const { toast } = useToast();

  return useMutation({
    mutationFn: candidatesService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["candidates"] });
      toast({
        title: "Success",
        description: "Candidate created successfully",
      });
    },
    onError: (error: any) => {
      toast({
        variant: "destructive",
        title: "Error",
        description: error.message || "Failed to create candidate",
      });
    },
  });
}

export function useUpdateCandidate() {
  const queryClient = useQueryClient();
  const { toast } = useToast();

  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: any }) =>
      candidatesService.update(id, data),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ["candidates"] });
      queryClient.invalidateQueries({ queryKey: ["candidates", variables.id] });
      toast({
        title: "Success",
        description: "Candidate updated successfully",
      });
    },
    onError: (error: any) => {
      toast({
        variant: "destructive",
        title: "Error",
        description: error.message || "Failed to update candidate",
      });
    },
  });
}

export function useDeleteCandidate() {
  const queryClient = useQueryClient();
  const { toast } = useToast();

  return useMutation({
    mutationFn: candidatesService.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["candidates"] });
      toast({
        title: "Success",
        description: "Candidate deleted successfully",
      });
    },
    onError: (error: any) => {
      toast({
        variant: "destructive",
        title: "Error",
        description: error.message || "Failed to delete candidate",
      });
    },
  });
}
```

**Update Component:**

**Before (Firebase):**

```typescript
// src/app/(authenticated)/candidates/page.tsx
import { useCollection } from "@/firebase";
import { collection } from "firebase/firestore";

const { data: candidates, isLoading } = useCollection<Candidate>(
  useMemoFirebase(
    () => (firestore ? collection(firestore, "candidates") : null),
    [firestore]
  )
);
```

**After (Laravel API):**

```typescript
// src/app/(authenticated)/candidates/page.tsx
import { useCandidates, useDeleteCandidate } from "@/hooks/use-candidates";

const { data, isLoading } = useCandidates({
  status: "Active",
  page: 1,
  per_page: 15,
});

const candidates = data?.data || [];
const pagination = data?.pagination;

const deleteMutation = useDeleteCandidate();

const handleDelete = async (id: string) => {
  await deleteMutation.mutateAsync(id);
};
```

---

## Complete Service Layer Examples

### Bookings Service

**File:** `src/lib/api/services/bookings.service.ts`

```typescript
import { apiClient, ApiResponse, PaginatedResponse } from "../client";
import type { BookingRequest, EnrichedBooking } from "@/lib/types";

export interface BookingQueryParams {
  status?: string;
  client_id?: number;
  job_role_id?: number;
  start_date?: string;
  end_date?: string;
  page?: number;
  per_page?: number;
}

export const bookingsService = {
  getAll: async (
    params?: BookingQueryParams
  ): Promise<ApiResponse<PaginatedResponse<EnrichedBooking>>> => {
    return apiClient.get("/bookings", { params });
  },

  getById: async (id: string): Promise<ApiResponse<EnrichedBooking>> => {
    return apiClient.get(`/bookings/${id}`);
  },

  create: async (
    data: Partial<BookingRequest>
  ): Promise<ApiResponse<BookingRequest>> => {
    return apiClient.post("/bookings", data);
  },

  update: async (
    id: string,
    data: Partial<BookingRequest>
  ): Promise<ApiResponse<BookingRequest>> => {
    return apiClient.put(`/bookings/${id}`, data);
  },

  cancel: async (id: string, reason: string): Promise<ApiResponse<void>> => {
    return apiClient.post(`/bookings/${id}/cancel`, {
      cancellation_reason: reason,
    });
  },
};
```

### Timesheets Service

**File:** `src/lib/api/services/timesheets.service.ts`

```typescript
import { apiClient, ApiResponse, PaginatedResponse } from "../client";
import type { Timesheet, EnrichedTimesheet } from "@/lib/types";

export const timesheetsService = {
  getAll: async (
    params?: any
  ): Promise<ApiResponse<PaginatedResponse<EnrichedTimesheet>>> => {
    return apiClient.get("/timesheets", { params });
  },

  getById: async (id: string): Promise<ApiResponse<EnrichedTimesheet>> => {
    return apiClient.get(`/timesheets/${id}`);
  },

  update: async (
    id: string,
    data: Partial<Timesheet>
  ): Promise<ApiResponse<Timesheet>> => {
    return apiClient.put(`/timesheets/${id}`, data);
  },

  submit: async (id: string): Promise<ApiResponse<void>> => {
    return apiClient.post(`/timesheets/${id}/submit`);
  },

  approve: async (id: string): Promise<ApiResponse<void>> => {
    return apiClient.post(`/timesheets/${id}/approve`);
  },

  reject: async (id: string, reason: string): Promise<ApiResponse<void>> => {
    return apiClient.post(`/timesheets/${id}/reject`, {
      rejection_reason: reason,
    });
  },
};
```

---

## File Upload Migration

**Before (Firebase Storage):**

```typescript
import { ref, uploadBytes, getDownloadURL } from "firebase/storage";
import { storage } from "@/firebase";

const storageRef = ref(storage, `compliance/${candidateId}/${file.name}`);
const snapshot = await uploadBytes(storageRef, file);
const downloadURL = await getDownloadURL(snapshot.ref);
```

**After (Laravel API):**

```typescript
import { candidatesService } from "@/lib/api/services/candidates.service";

const response = await candidatesService.uploadCompliance(
  candidateId,
  complianceId,
  file,
  expiryDate
);

if (response.success) {
  const fileUrl = response.data.file_url;
  // File is uploaded and URL is returned
}
```

---

## Migration Checklist

### Phase 1: Setup ✓

- [ ] Install dependencies (axios, react-query)
- [ ] Create API client
- [ ] Setup environment variables
- [ ] Create auth context
- [ ] Add query client provider

### Phase 2: Authentication ✓

- [ ] Replace Firebase auth with API auth
- [ ] Update login page
- [ ] Update logout functionality
- [ ] Add token refresh logic
- [ ] Update protected route middleware

### Phase 3: Data Fetching ✓

- [ ] Create service layer for each entity
- [ ] Create custom hooks with React Query
- [ ] Update all pages to use new hooks
- [ ] Handle loading states
- [ ] Handle error states

### Phase 4: File Uploads ✓

- [ ] Replace Firebase Storage with API uploads
- [ ] Update compliance document uploads
- [ ] Update avatar uploads
- [ ] Update company logo uploads

### Phase 5: Real-time Features

- [ ] Replace Firestore real-time listeners with polling or WebSockets
- [ ] Implement notifications polling
- [ ] Update notification bell component

### Phase 6: Testing ✓

- [ ] Test all API endpoints
- [ ] Test authentication flow
- [ ] Test CRUD operations
- [ ] Test file uploads
- [ ] Test error handling
- [ ] Test pagination

### Phase 7: Cleanup

- [ ] Remove Firebase dependencies
- [ ] Remove Firebase configuration
- [ ] Update environment variables
- [ ] Update documentation

---

## Error Handling

**Create Error Handler:**

**File:** `src/lib/api/error-handler.ts`

```typescript
import { AxiosError } from "axios";

export interface ApiError {
  code: string;
  message: string;
  details?: any;
}

export function handleApiError(error: unknown): ApiError {
  if (error instanceof AxiosError) {
    const apiError = error.response?.data?.error;

    if (apiError) {
      return apiError;
    }

    // Network or timeout error
    if (error.code === "ECONNABORTED") {
      return {
        code: "TIMEOUT",
        message: "Request timeout. Please try again.",
      };
    }

    if (error.code === "ERR_NETWORK") {
      return {
        code: "NETWORK_ERROR",
        message: "Network error. Please check your connection.",
      };
    }

    return {
      code: "UNKNOWN_ERROR",
      message: error.message || "An unexpected error occurred",
    };
  }

  return {
    code: "UNKNOWN_ERROR",
    message: "An unexpected error occurred",
  };
}
```

---

## Testing API Integration

**Create API Test Utilities:**

**File:** `src/lib/api/__tests__/test-utils.ts`

```typescript
import { apiClient } from "../client";

// Mock API responses for testing
export const mockApiResponse = <T>(data: T, success = true) => ({
  success,
  data,
});

export const mockApiError = (message: string, code = "ERROR") => ({
  success: false,
  error: {
    code,
    message,
  },
});

// Reset auth token for testing
export const resetAuth = () => {
  if (typeof window !== "undefined") {
    localStorage.removeItem("auth_token");
  }
};
```

---

## Summary

This migration guide provides:

✅ Complete API client setup with authentication  
✅ Auth context replacement  
✅ Service layer architecture  
✅ React Query integration  
✅ Custom hooks for data fetching  
✅ File upload handling  
✅ Error handling  
✅ Testing utilities  
✅ Migration checklist

**Next Steps:**

1. Follow the setup instructions
2. Implement authentication first
3. Migrate one feature at a time (start with candidates)
4. Test thoroughly before moving to the next feature
5. Keep both systems running in parallel during migration
6. Complete cleanup after full migration

**Estimated Timeline:** 2-3 weeks for complete frontend migration

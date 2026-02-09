import { describe, it, expect, beforeEach, vi } from 'vitest';

import {
  useDutyUserWizard,
  getSuggestedEndDate,
  formatDateForDisplay,
  getTodayDate,
} from '../useDutyUserWizard';

// Mock inertia - handled by global setup
vi.mock('@inertiajs/vue3', () => ({
  router: {
    post: vi.fn(),
    visit: vi.fn(),
    reload: vi.fn(),
  },
  usePage: () => ({
    props: {
      auth: { user: { id: 1, name: 'Test User' } },
    },
  }),
}));

// Mock laravel-vue-i18n
vi.mock('laravel-vue-i18n', () => ({
  trans: (key: string) => key,
}));

// Mock route helper
vi.mock('ziggy-js', () => ({
  default: (name: string) => `/mock/${name}`,
}));

// Make route globally available
vi.stubGlobal('route', (name: string) => `/mock/${name}`);

describe('useDutyUserWizard', () => {
  describe('initialization', () => {
    it('initializes with default state', () => {
      const wizard = useDutyUserWizard();

      expect(wizard.state.currentStep).toBe(1);
      expect(wizard.state.maxCompletedStep).toBe(0);
      expect(wizard.state.institution).toBeUndefined();
      expect(wizard.state.duty).toBeUndefined();
      expect(wizard.state.userChanges).toEqual([]);
      expect(wizard.state.newUsersToCreate).toEqual([]);
    });

    it('initializes at step 2 with pre-selected institution', () => {
      const institution = { id: 'inst1', name: 'Test Institution' } as unknown as App.Entities.Institution;
      const wizard = useDutyUserWizard({ preSelectedInstitution: institution });

      expect(wizard.state.currentStep).toBe(2);
      expect(wizard.state.maxCompletedStep).toBe(1);
      expect(wizard.state.institution).toEqual(institution);
    });

    it('initializes at step 3 with pre-selected duty', () => {
      const institution = { id: 'inst1', name: 'Test Institution' } as unknown as App.Entities.Institution;
      const duty = { id: 'duty1', name: 'Test Duty' } as unknown as App.Entities.Duty;
      const wizard = useDutyUserWizard({
        preSelectedInstitution: institution,
        preSelectedDuty: duty,
      });

      expect(wizard.state.currentStep).toBe(3);
      expect(wizard.state.maxCompletedStep).toBe(2);
      expect(wizard.state.duty).toEqual(duty);
    });
  });

  describe('step navigation', () => {
    it('can go to next step after validation', () => {
      const wizard = useDutyUserWizard();
      const institution = { id: 'inst1', name: 'Test Institution' } as unknown as App.Entities.Institution;

      wizard.setInstitution(institution);
      wizard.nextStep();

      expect(wizard.state.currentStep).toBe(2);
    });

    it('cannot go to next step without validation', () => {
      const wizard = useDutyUserWizard();

      wizard.nextStep();

      expect(wizard.state.currentStep).toBe(1);
    });

    it('can go to previous step', () => {
      const wizard = useDutyUserWizard();
      const institution = { id: 'inst1', name: 'Test Institution' } as unknown as App.Entities.Institution;

      wizard.setInstitution(institution);
      wizard.nextStep();
      wizard.previousStep();

      expect(wizard.state.currentStep).toBe(1);
    });

    it('cannot go to previous step from step 1', () => {
      const wizard = useDutyUserWizard();

      wizard.previousStep();

      expect(wizard.state.currentStep).toBe(1);
    });

    it('can jump to completed step', () => {
      const wizard = useDutyUserWizard();
      const institution = { id: 'inst1', name: 'Test Institution' } as unknown as App.Entities.Institution;
      const duty = { id: 'duty1', name: 'Test Duty' } as unknown as App.Entities.Duty;

      wizard.setInstitution(institution);
      wizard.nextStep();
      wizard.setDuty(duty);
      wizard.nextStep();

      wizard.goToStep(1);
      expect(wizard.state.currentStep).toBe(1);

      wizard.goToStep(2);
      expect(wizard.state.currentStep).toBe(2);
    });

    it('cannot jump to uncompleted step', () => {
      const wizard = useDutyUserWizard();

      wizard.goToStep(3);

      expect(wizard.state.currentStep).toBe(1);
    });
  });

  describe('setInstitution', () => {
    it('sets institution and validates', () => {
      const wizard = useDutyUserWizard();
      const institution = { id: 'inst1', name: 'Test Institution' } as unknown as App.Entities.Institution;

      wizard.setInstitution(institution);

      expect(wizard.state.institution).toEqual(institution);
      expect(wizard.state.validation.institution).toBe(true);
    });

    it('resets duty and users when institution changes', () => {
      const wizard = useDutyUserWizard();
      const institution1 = { id: 'inst1', name: 'Institution 1' } as unknown as App.Entities.Institution;
      const institution2 = { id: 'inst2', name: 'Institution 2' } as unknown as App.Entities.Institution;
      const duty = { id: 'duty1', name: 'Test Duty' } as unknown as App.Entities.Duty;

      wizard.setInstitution(institution1);
      wizard.setDuty(duty);
      wizard.setInstitution(institution2);

      expect(wizard.state.duty).toBeUndefined();
      expect(wizard.state.userChanges).toEqual([]);
    });
  });

  describe('setDuty', () => {
    it('sets duty and validates', () => {
      const wizard = useDutyUserWizard();
      const duty = { id: 'duty1', name: 'Test Duty' } as unknown as App.Entities.Duty;

      wizard.setDuty(duty);

      expect(wizard.state.duty).toEqual(duty);
      expect(wizard.state.validation.duty).toBe(true);
    });

    it('resets user changes when duty changes', () => {
      const wizard = useDutyUserWizard();
      const duty1 = { id: 'duty1', name: 'Duty 1' } as unknown as App.Entities.Duty;
      const duty2 = { id: 'duty2', name: 'Duty 2' } as unknown as App.Entities.Duty;
      const user = { id: 'user1', name: 'Test User', email: 'test@test.com' } as unknown as App.Entities.User;

      wizard.setDuty(duty1);
      wizard.addUserToAdd(user);
      wizard.setDuty(duty2);

      expect(wizard.state.userChanges).toEqual([]);
    });
  });

  describe('user changes', () => {
    it('adds user to add list', () => {
      const wizard = useDutyUserWizard();
      const user = {
        id: 'user1',
        name: 'Test User',
        email: 'test@test.com',
        profile_photo_path: null,
      } as unknown as App.Entities.User;

      wizard.addUserToAdd(user);

      expect(wizard.state.userChanges).toHaveLength(1);
      expect(wizard.state.userChanges[0]?.action).toBe('add');
      expect(wizard.state.userChanges[0]?.userId).toBe('user1');
    });

    it('adds user to remove list', () => {
      const wizard = useDutyUserWizard();
      const user = {
        id: 'user1',
        name: 'Test User',
        email: 'test@test.com',
        profile_photo_path: null,
      } as unknown as App.Entities.User;

      wizard.addUserToRemove(user);

      expect(wizard.state.userChanges).toHaveLength(1);
      expect(wizard.state.userChanges[0]?.action).toBe('remove');
    });

    it('removes user change', () => {
      const wizard = useDutyUserWizard();
      const user = {
        id: 'user1',
        name: 'Test User',
        email: 'test@test.com',
        profile_photo_path: null,
      } as unknown as App.Entities.User;

      wizard.addUserToAdd(user);
      wizard.removeUserChange('user1');

      expect(wizard.state.userChanges).toHaveLength(0);
    });

    it('sets dates for added users', () => {
      const wizard = useDutyUserWizard();
      const user = {
        id: 'user1',
        name: 'Test User',
        email: 'test@test.com',
        profile_photo_path: null,
      } as unknown as App.Entities.User;

      wizard.addUserToAdd(user, {
        startDate: '2025-01-01',
        endDate: '2025-12-31',
      });

      expect(wizard.state.userChanges[0]?.startDate).toBe('2025-01-01');
      expect(wizard.state.userChanges[0]?.endDate).toBe('2025-12-31');
    });

    it('can add study program to user change', () => {
      const wizard = useDutyUserWizard();
      const user = {
        id: 'user1',
        name: 'Test User',
        email: 'test@test.com',
        profile_photo_path: null,
      } as unknown as App.Entities.User;

      wizard.addUserToAdd(user, {
        studyProgramId: 'sp1',
      });

      expect(wizard.state.userChanges[0]?.studyProgramId).toBe('sp1');
    });
  });

  describe('validation', () => {
    it('validates additions require start dates', () => {
      const wizard = useDutyUserWizard();
      const duty = { id: 'duty1', name: 'Test Duty' } as unknown as App.Entities.Duty;
      const user = { id: 'user1', name: 'Test', email: 'test@test.com', profile_photo_path: null } as unknown as App.Entities.User;

      wizard.setDuty(duty);

      // Add user (which sets dates) then remove the start date
      wizard.addUserToAdd(user);
      wizard.updateUserChange('user1', { startDate: undefined });

      const result = wizard.validateUsers();

      expect(result).toBe(false);
      expect(wizard.state.errors['start_date']).toBeDefined();
    });

    it('validates end date cannot be before start date', () => {
      const wizard = useDutyUserWizard();
      const duty = { id: 'duty1', name: 'Test Duty' } as unknown as App.Entities.Duty;
      const user = { id: 'user1', name: 'Test', email: 'test@test.com', profile_photo_path: null } as unknown as App.Entities.User;

      wizard.setDuty(duty);

      // Add user and set invalid date range
      wizard.addUserToAdd(user, {
        startDate: '2025-12-31',
        endDate: '2025-01-01', // Before start date
      });

      const result = wizard.validateUsers();

      expect(result).toBe(false);
      expect(wizard.state.errors['date_range']).toBeDefined();
    });
  });

  describe('capacity calculations', () => {
    it('calculates projected user count', () => {
      const wizard = useDutyUserWizard();
      const duty = {
        id: 'duty1',
        name: 'Test Duty',
        current_users: [{ id: 'u1' }, { id: 'u2' }] as any[],
      } as unknown as App.Entities.Duty;

      wizard.setDuty(duty);

      // Add one user
      wizard.addUserToAdd({ id: 'user3', name: 'New User', email: 'new@test.com', profile_photo_path: null } as unknown as App.Entities.User);

      expect(wizard.projectedUserCount.value).toBe(3);
    });

    it('detects capacity mismatch', () => {
      const wizard = useDutyUserWizard();
      const duty = {
        id: 'duty1',
        name: 'Test Duty',
        current_users: [{ id: 'u1' }] as any[],
        places_to_occupy: 3,
      } as unknown as App.Entities.Duty;

      wizard.setDuty(duty);

      expect(wizard.capacityMismatch.value).toBe(true); // 1 user vs 3 places
    });

    it('can update places to occupy', () => {
      const wizard = useDutyUserWizard();
      const duty = {
        id: 'duty1',
        name: 'Test Duty',
        places_to_occupy: 3,
      } as unknown as App.Entities.Duty;

      wizard.setDuty(duty);
      wizard.setNewPlacesToOccupy(5);

      expect(wizard.targetCapacity.value).toBe(5);
    });
  });

  describe('hasChanges', () => {
    it('returns false with no changes', () => {
      const wizard = useDutyUserWizard();

      expect(wizard.hasChanges.value).toBe(false);
    });

    it('returns true with user changes', () => {
      const wizard = useDutyUserWizard();
      const user = { id: 'user1', name: 'Test', email: 'test@test.com', profile_photo_path: null } as unknown as App.Entities.User;

      wizard.addUserToAdd(user);

      expect(wizard.hasChanges.value).toBe(true);
    });

    it('returns true with capacity change', () => {
      const wizard = useDutyUserWizard();

      wizard.setNewPlacesToOccupy(5);

      expect(wizard.hasChanges.value).toBe(true);
    });
  });

  describe('reset', () => {
    it('resets to initial state', () => {
      const wizard = useDutyUserWizard();
      const institution = { id: 'inst1', name: 'Test Institution' } as unknown as App.Entities.Institution;
      const duty = { id: 'duty1', name: 'Test Duty' } as unknown as App.Entities.Duty;

      wizard.setInstitution(institution);
      wizard.setDuty(duty);
      wizard.reset();

      expect(wizard.state.currentStep).toBe(1);
      expect(wizard.state.institution).toBeUndefined();
      expect(wizard.state.duty).toBeUndefined();
    });
  });
});

describe('utility functions', () => {
  describe('getSuggestedEndDate', () => {
    it('returns a date string in YYYY-MM-DD format', () => {
      const date = getSuggestedEndDate();
      expect(date).toMatch(/^\d{4}-07-01$/);
    });

    it('always returns July 1st', () => {
      const date = getSuggestedEndDate();
      expect(date.endsWith('-07-01')).toBe(true);
    });
  });

  describe('getTodayDate', () => {
    it('returns today in YYYY-MM-DD format', () => {
      const today = getTodayDate();
      const expected = new Date().toISOString().split('T')[0];
      expect(today).toBe(expected);
    });
  });

  describe('formatDateForDisplay', () => {
    it('formats date in localized format', () => {
      const formatted = formatDateForDisplay('2025-07-01');
      expect(formatted).toContain('2025');
    });

    it('returns empty string for empty input', () => {
      const formatted = formatDateForDisplay('');
      expect(formatted).toBe('');
    });
  });
});

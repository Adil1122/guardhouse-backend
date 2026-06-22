<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\ComplianceController;
use App\Http\Controllers\Api\CustomerContactController;
use App\Http\Controllers\Api\CustomerInvoiceProfileController;
use App\Http\Controllers\Api\SiteContactController;
use App\Http\Controllers\Api\PayGroupController;
use App\Http\Controllers\Api\PayRateController;
use App\Http\Controllers\Api\ServiceGroupController;
use App\Http\Controllers\Api\ServiceRateController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\SitePreferenceController;
use App\Http\Controllers\Api\SiteCheckpointController;
use App\Http\Controllers\Api\SiteDocumentController;
use App\Http\Controllers\Api\SiteAccessCodeController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\ShiftNoteController;
use App\Http\Controllers\Api\TimesheetController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoiceItemController;
use App\Http\Controllers\Api\ShiftTimeClockLogController;
use App\Http\Controllers\Api\DigitalOccurrenceLogController;
use App\Http\Controllers\Api\TimezoneController;
use App\Http\Controllers\Api\LiveOperationController;
use App\Http\Controllers\Api\WorkerGeofenceController;
use App\Http\Controllers\Api\WorkerController;
use App\Http\Controllers\Api\SupervisorController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\CustomerInvoiceController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\TeamMessageController;

Route::prefix('auth')->group(function () {
    Route::middleware(['throttle:login', 'check.honeypot'])->post('login', [AuthController::class, 'login']);
    Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('/verify-reset-password-token', [AuthController::class, 'verifyResetPasswordToken']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/activate-user', [AuthController::class, 'activateUser']);
    Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
});

Route::get('timezones', [TimezoneController::class, 'index']);

//Route::middleware(['auth:sanctum', 'ensure.user.access', 'check.referer'])->group(function () {
    
    //Route::middleware('ability:admin')->group(function () {
        Route::middleware(['throttle:signup', 'check.honeypot'])->post('auth/signup', [RegisterController::class, 'signup']); // rate limiting + honeypot checking middlewares in place
        
        Route::prefix('users/{id}/privileges')->group(function () {
            Route::get('', [StaffController::class, 'listPrivileges'])->name('user.privilege.list');
            Route::patch('', [StaffController::class, 'updatePrivileges'])->name('user.privilege.update');
        });

        Route::prefix('organization')->group(function () {
            Route::prefix('compliances')->group(function () {
                Route::get('', [ComplianceController::class, 'index'])->name('organization_compliance.list');
                Route::get('paginated/{page}/{filter?}', [ComplianceController::class, 'paginatedList'])->name('organization_compliance.list');
                Route::post('', [ComplianceController::class, 'store'])->name('organization_compliance.create');
                Route::patch('{id}', [ComplianceController::class, 'update'])->name('organization_compliance.update');
                Route::delete('{id}', [ComplianceController::class, 'destroy'])->name('organization_compliance.delete');
            });

            Route::patch('settings', [OrganizationController::class, 'settings'])->name('organization.settings');
        });

        Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
            Route::get('system-statistics', [StatisticsController::class, 'index'])->name('admin.statistics');
            Route::get('activities', [StatisticsController::class, 'activities'])->name('admin.activities');
            Route::get('system-logs', [StatisticsController::class, 'systemLogs'])->name('admin.system_logs');
            Route::get('clock-in-questionnaires', [FormController::class, 'clockInQuestionnaires'])->name('admin.clock_in_questionnaires');

            Route::prefix('alerts')->group(function () {
                Route::get('', [AlertController::class, 'index'])->name('admin.alerts.list');
                Route::post('read-all', [AlertController::class, 'markAllRead'])->name('admin.alerts.read_all');
                Route::post('{id}/read', [AlertController::class, 'markRead'])->name('admin.alerts.read');
                Route::delete('{id}', [AlertController::class, 'destroy'])->name('admin.alerts.delete');
            });

            Route::prefix('occurrence-logs')->group(function () {
                Route::get('', [DigitalOccurrenceLogController::class, 'index'])->name('admin.occurrence_log.list');
                Route::post('', [DigitalOccurrenceLogController::class, 'store'])->name('admin.occurrence_log.create');
                Route::patch('{id}', [DigitalOccurrenceLogController::class, 'update'])->name('admin.occurrence_log.update');
                Route::delete('{id}', [DigitalOccurrenceLogController::class, 'destroy'])->name('admin.occurrence_log.delete');
            });

            Route::prefix('shifts/notes')->group(function () {
                Route::get('', [ShiftNoteController::class, 'all'])->name('admin.shift_notes.list');
                Route::post('', [ShiftNoteController::class, 'store'])->name('admin.shift_notes.create');
                Route::patch('{id}', [ShiftNoteController::class, 'update'])->name('admin.shift_notes.update');
                Route::delete('{id}', [ShiftNoteController::class, 'destroy'])->name('admin.shift_notes.delete');
            });

            Route::get('alarms', [SupervisorController::class, 'adminAlarmHistory'])->name('admin.alarms.list');
            Route::post('alarms', [SupervisorController::class, 'adminRaiseAlarm'])->name('admin.alarms.raise');

            Route::get('team-messages', [TeamMessageController::class, 'adminIndex'])->name('admin.team_messages.list');
            Route::post('team-messages', [TeamMessageController::class, 'store'])->name('admin.team_messages.create');
            Route::delete('team-messages/{id}', [TeamMessageController::class, 'destroy'])->name('admin.team_messages.delete');
            Route::get('team-messages/{id}/replies', [TeamMessageController::class, 'adminReplies'])->name('admin.team_messages.replies.list');
            Route::post('team-messages/{id}/replies', [TeamMessageController::class, 'adminStoreReply'])->name('admin.team_messages.replies.create');
        });

        Route::get('organization/company-settings', [OrganizationController::class, 'getSettings'])->name('organization.get_settings');
        Route::put('organization/company-settings', [OrganizationController::class, 'settings'])->name('organization.update_settings');
    //});

    Route::prefix('shifts')->group(function () {
        Route::get('', [ShiftController::class, 'index'])->name('shift.list');
        Route::get('paginated/{page}/{filter?}', [ShiftController::class, 'paginatedList'])->name('shift.list');
        Route::post('', [ShiftController::class, 'store'])->name('shift.create');
        Route::patch('{id}', [ShiftController::class, 'update'])->name('shift.update');
        Route::delete('{id}', [ShiftController::class, 'destroy'])->name('shift.delete');

        Route::prefix('{shiftId}/notes')->middleware('bind.shift.id')->group(function () {
            Route::get('', [ShiftNoteController::class, 'index'])->name('shift.list');
            Route::post('', [ShiftNoteController::class, 'store'])->name('shift.create');
            Route::patch('{id}', [ShiftNoteController::class, 'update'])->name('shift.update');
            Route::delete('{id}', [ShiftNoteController::class, 'destroy'])->name('shift.delete');
        });
        
        Route::prefix('{shiftId}/submission')->middleware('bind.shift.id')->group(function () {
            Route::post('timeclock', [ShiftTimeClockLogController::class, 'store'])->name('shift_submission.timeclock.create')->middleware('bind.shift.clock.in.out.time');
        });

    });
    
    Route::prefix('timesheets')->group(function () {
        Route::get('', [TimesheetController::class, 'index'])->name('timesheet.list');
        Route::get('by-status', [TimesheetController::class, 'byStatus'])->name('timesheet.byStatus');
        Route::get('paginated/{page}/{filter?}', [TimesheetController::class, 'paginatedList'])->name('timesheet.list');
        Route::patch('{id}', [TimesheetController::class, 'update'])->name('timesheet.update')->middleware('can.edit.timesheet');
        Route::post('{id}/approve', [TimesheetController::class, 'approve'])->name('timesheet.approve');
        Route::post('{id}/reject', [TimesheetController::class, 'reject'])->name('timesheet.reject');
    });

    Route::prefix('customers')->group(function () {
        Route::get('', [CustomerController::class, 'index'])->name('customer.list');
        Route::get('paginated/{page}/{filter?}', [CustomerController::class, 'paginatedList'])->name('customer.list');
        Route::post('', [CustomerController::class, 'store'])->name('customer.create');
        Route::get('{id}', [CustomerController::class, 'show'])->name('customer.show');
        Route::patch('{id}', [CustomerController::class, 'update'])->name('customer.update');
        Route::delete('{id}', [CustomerController::class, 'destroy'])->name('customer.delete');

        Route::prefix('{userId}/sites')->middleware('bind.customer.profile.id')->group(function () {
            Route::get('', [CustomerController::class, 'sitesList'])->name('customer.list');
            Route::post('', [CustomerController::class, 'attachSite'])->name('customer.create');
            Route::delete('{id}', [CustomerController::class, 'detachSite'])->name('customer.delete');
        });

        Route::prefix('{userId}/contacts')->middleware('bind.customer.profile.id')->group(function () {
            Route::get('', [CustomerContactController::class, 'index'])->name('customer.list');
            Route::get('paginated/{page}/{filter?}', [CustomerContactController::class, 'paginatedList'])->name('customer.list');
            Route::post('', [CustomerContactController::class, 'store'])->name('customer.create');
            Route::patch('{id}', [CustomerContactController::class, 'update'])->name('customer.update');
            Route::delete('{id}', [CustomerContactController::class, 'destroy'])->name('customer.delete');
        });
        
        Route::prefix('{userId}/invoice-profiles')->middleware('bind.customer.profile.id')->group(function () {
            Route::get('', [CustomerInvoiceProfileController::class, 'index'])->name('customer.list');
            Route::get('paginated/{page}/{filter?}', [CustomerInvoiceProfileController::class, 'paginatedList'])->name('customer.list');
            Route::post('', [CustomerInvoiceProfileController::class, 'store'])->name('customer.create');
            Route::patch('{id}', [CustomerInvoiceProfileController::class, 'update'])->name('customer.update');
            Route::delete('{id}', [CustomerInvoiceProfileController::class, 'destroy'])->name('customer.delete');
        });

        Route::prefix('{customerId}/invoices')->group(function () {
            Route::get('', [CustomerInvoiceController::class, 'index'])->name('customer.invoices.list');
            Route::post('', [CustomerInvoiceController::class, 'store'])->name('customer.invoices.create');
            Route::patch('{id}', [CustomerInvoiceController::class, 'update'])->name('customer.invoices.update');
            Route::delete('{id}', [CustomerInvoiceController::class, 'destroy'])->name('customer.invoices.delete');
        });
    });
    
    Route::prefix('customer-invoice-profiles')->group(function () {
        Route::get('', [CustomerInvoiceProfileController::class, 'all'])->name('customer_invoice_profile.all');
    });
    
    Route::prefix('sites')->group(function () {
        Route::get('', [SiteController::class, 'index'])->name('static_site.list');
        Route::get('paginated/{page}/{filter?}', [SiteController::class, 'paginatedList'])->name('static_site.list');
        Route::post('', [SiteController::class, 'store'])->name('static_site.create');
        Route::get('{id}', [SiteController::class, 'show'])->name('static_site.show');
        Route::patch('{id}', [SiteController::class, 'update'])->name('static_site.update');
        Route::delete('{id}', [SiteController::class, 'destroy'])->name('static_site.delete');

        Route::prefix('{siteId}/contacts')->middleware('bind.site.id')->group(function () {
            Route::get('', [SiteContactController::class, 'index'])->name('static_site.list');
            Route::get('paginated/{page}/{filter?}', [SiteContactController::class, 'paginatedList'])->name('static_site.list');
            Route::post('', [SiteContactController::class, 'store'])->name('static_site.create');
            Route::patch('{id}', [SiteContactController::class, 'update'])->name('static_site.update');
            Route::delete('{id}', [SiteContactController::class, 'destroy'])->name('static_site.delete');
        });
        
        Route::prefix('{siteId}/preferences')->middleware('bind.site.id')->group(function () {
            Route::get('', [SitePreferenceController::class, 'index'])->name('static_site.list');
            Route::get('paginated/{page}/{filter?}', [SitePreferenceController::class, 'paginatedList'])->name('static_site.list');
            Route::post('', [SitePreferenceController::class, 'store'])->name('static_site.create');
            Route::patch('{id}', [SitePreferenceController::class, 'update'])->name('static_site.update');
            Route::delete('{id}', [SitePreferenceController::class, 'destroy'])->name('static_site.delete');
        });

        Route::prefix('{siteId}/checkpoints')->middleware('bind.site.id')->group(function () {
            Route::get('', [SiteCheckpointController::class, 'index'])->name('static_site.list');
            Route::get('paginated/{page}/{filter?}', [SiteCheckpointController::class, 'paginatedList'])->name('static_site.list');
            Route::post('', [SiteCheckpointController::class, 'store'])->name('static_site.create');
            Route::patch('{id}', [SiteCheckpointController::class, 'update'])->name('static_site.update');
            Route::delete('{id}', [SiteCheckpointController::class, 'destroy'])->name('static_site.delete');
        });
        
        Route::prefix('{siteId}/documents')->middleware('bind.site.id')->group(function () {
            Route::get('', [SiteDocumentController::class, 'index'])->name('static_site.list');
            Route::get('paginated/{page}/{filter?}', [SiteDocumentController::class, 'paginatedList'])->name('static_site.list');
            Route::post('', [SiteDocumentController::class, 'store'])->name('static_site.create');
            Route::patch('{id}', [SiteDocumentController::class, 'update'])->name('static_site.update');
            Route::delete('{id}', [SiteDocumentController::class, 'destroy'])->name('static_site.delete');
            Route::post('{id}/upload-files', [SiteDocumentController::class, 'uploadFiles'])->name('static_site.update');
            Route::delete('{id}/delete-file', [SiteDocumentController::class, 'deleteFile'])->name('static_site.delete');
        });
        
        Route::prefix('{siteId}/access-codes')->middleware('bind.site.id')->group(function () {
            Route::get('', [SiteAccessCodeController::class, 'index'])->name('static_site.list');
            Route::get('paginated/{page}/{filter?}', [SiteAccessCodeController::class, 'paginatedList'])->name('static_site.list');
            Route::post('', [SiteAccessCodeController::class, 'store'])->name('static_site.create');
            Route::patch('{id}', [SiteAccessCodeController::class, 'update'])->name('static_site.update');
            Route::delete('{id}', [SiteAccessCodeController::class, 'destroy'])->name('static_site.delete');
        });
    });
    
    Route::prefix('staff')->group(function () {
        Route::get('', [StaffController::class, 'index'])->name('staff.list');
        Route::get('paginated/{page}/{filter?}', [StaffController::class, 'paginatedList'])->name('staff.list');
        Route::middleware(['throttle:signup', 'check.honeypot'])->post('', [RegisterController::class, 'signup'])->name('staff.create'); // rate limiting + honeypot checking middlewares in place
        Route::get('{id}', [StaffController::class, 'show'])->name('staff.show');
        Route::patch('{id}', [StaffController::class, 'update'])->name('staff.update');
        Route::delete('{id}', [StaffController::class, 'destroy'])->name('staff.delete');
        
        Route::patch('{id}/salary', [StaffController::class, 'updatePaySalary'])->name('staff.salary');
        Route::patch('{id}/privilege', [StaffController::class, 'updatePrivileges'])->name('staff.privilege');
        Route::get('{id}/privilege', [StaffController::class, 'listPrivileges'])->name('staff.privilege');
        Route::prefix('{userId}/compliances')->group(function () {
            Route::get('', [StaffController::class, 'listCompliances'])->name('staff.compliance');
            Route::post('', [StaffController::class, 'createCompliance'])->name('staff.compliance');
            Route::delete('', [StaffController::class, 'deleteCompliance'])->name('staff.compliance');
        });
    });

    Route::prefix('pay-groups')->group(function () {
        Route::get('', [PayGroupController::class, 'index'])->name('pay_group.list');
        Route::get('paginated/{page}/{filter?}', [PayGroupController::class, 'paginatedList'])->name('pay_group.list');
        Route::post('', [PayGroupController::class, 'store'])->name('pay_group.create');
        Route::patch('{id}', [PayGroupController::class, 'update'])->name('pay_group.update');
        Route::delete('{id}', [PayGroupController::class, 'destroy'])->name('pay_group.delete');
        
        // Pay Rates for a Pay Group
        Route::get('{groupId}/rates', [PayRateController::class, 'index'])->name('pay_group.list');
        Route::post('{groupId}/rates', [PayRateController::class, 'store'])->name('pay_group.create');
    });

    Route::prefix('pay-rates')->group(function () {
        Route::get('{id}', [PayRateController::class, 'show'])->name('pay_group.list');
        Route::patch('{id}', [PayRateController::class, 'update'])->name('pay_group.update');
        Route::delete('{id}', [PayRateController::class, 'destroy'])->name('pay_group.delete');
    });

    Route::prefix('service-groups')->group(function () {
        Route::get('', [ServiceGroupController::class, 'index'])->name('service_group.list');
        Route::get('paginated/{page}/{filter?}', [ServiceGroupController::class, 'paginatedList'])->name('service_group.list');
        Route::post('', [ServiceGroupController::class, 'store'])->name('service_group.create');
        Route::patch('{id}', [ServiceGroupController::class, 'update'])->name('service_group.update');
        Route::delete('{id}', [ServiceGroupController::class, 'destroy'])->name('service_group.delete');
        
        // Service Rates for a Service Group
        Route::get('{groupId}/rates', [ServiceRateController::class, 'index'])->name('service_group.list');
        Route::post('{groupId}/rates', [ServiceRateController::class, 'store'])->name('service_group.create');
    });

    Route::prefix('service-rates')->group(function () {
        Route::get('{id}', [ServiceRateController::class, 'show'])->name('service_group.list');
        Route::patch('{id}', [ServiceRateController::class, 'update'])->name('service_group.update');
        Route::delete('{id}', [ServiceRateController::class, 'destroy'])->name('service_group.delete');
    });

    Route::prefix('forms')->group(function () {
        Route::get('{filter?}', [FormController::class, 'index'])->name('form.list');
        Route::get('paginated/{page}/{filter?}', [FormController::class, 'paginatedList'])->name('form.list');
        Route::post('', [FormController::class, 'store'])->name('form.create');
        Route::patch('{id}', [FormController::class, 'update'])->name('form.update');
        Route::delete('{id}', [FormController::class, 'destroy'])->name('form.delete');
        Route::patch('{id}/update-element-order', [FormController::class, 'updateElementOrder'])->name('form.update');
    });
    
    Route::prefix('invoices')->group(function () {
        Route::get('{filter?}', [InvoiceController::class, 'index'])->name('invoice.list');
        Route::get('paginated/{page}/{filter?}', [InvoiceController::class, 'paginatedList'])->name('invoice.list');
        Route::post('', [InvoiceController::class, 'store'])->name('invoice.create');
        Route::patch('{id}', [InvoiceController::class, 'update'])->name('invoice.update')->middleware('can.edit.invoice');
        Route::delete('{id}', [InvoiceController::class, 'destroy'])->name('invoice.delete');
        Route::patch('{id}/complete', [InvoiceController::class, 'complete'])->name('invoice.complete');
        
        // Invoice Items CRUD
        Route::post('{invoiceId}/items', [InvoiceItemController::class, 'store'])->name('invoice.create');
        Route::patch('{invoiceId}/items/{itemId}', [InvoiceItemController::class, 'update'])->name('invoice.update');
        Route::delete('{invoiceId}/items/{itemId}', [InvoiceItemController::class, 'destroy'])->name('invoice.delete');
    });

    Route::prefix('digital-occurence-logs')->group(function () {
        Route::get('', [DigitalOccurrenceLogController::class, 'index'])->name('digital_occurence_log.list');
        Route::get('paginated/{page}/{filter?}', [DigitalOccurrenceLogController::class, 'paginatedList'])->name('digital_occurence_log.list');
        Route::patch('{id}', [DigitalOccurrenceLogController::class, 'update'])->name('digital_occurence_log.update');
        Route::delete('{id}', [DigitalOccurrenceLogController::class, 'destroy'])->name('digital_occurence_log.delete');
    });

    Route::prefix('live-operations')->group(function () {
        Route::get('', [LiveOperationController::class, 'index'])->name('live_operation.list');
    });

    // Worker Geofencing Routes - Move outside restrictive middleware
//});

Route::middleware(['auth:sanctum'])->prefix('worker')->group(function () {
    // Geofence endpoints
    Route::get('/current-shift', [WorkerGeofenceController::class, 'getCurrentShift']);
    Route::post('/location', [WorkerGeofenceController::class, 'updateLocation']);
    Route::post('/checkin', [WorkerGeofenceController::class, 'submitCheckin']);
    Route::post('/qr-scan', [WorkerGeofenceController::class, 'scanQrCheckpoint']);
    Route::get('/checkin-history', [WorkerGeofenceController::class, 'getCheckinHistory']);
    Route::get('/duty-history', [WorkerGeofenceController::class, 'getDutyHistory']);
    Route::get('/shift-history', [WorkerGeofenceController::class, 'getDutyHistory']); // alias: Flutter calls shift-history
    Route::get('/shift-details/{shiftId}', [WorkerGeofenceController::class, 'getShiftDetails']);
    Route::get('/shifts/{shiftId}', [WorkerGeofenceController::class, 'getShiftDetails']); // alias: Flutter calls shifts/{id}

    // Checkin CRUD endpoints
    Route::get('/checkins/{id}', [WorkerGeofenceController::class, 'getCheckin']);
    Route::put('/checkins/{id}', [WorkerGeofenceController::class, 'updateCheckin']);
    Route::delete('/checkins/{id}', [WorkerGeofenceController::class, 'deleteCheckin']);

    // Worker endpoints
    Route::get('sites', [WorkerController::class, 'sites']);
    Route::get('assigned-site', [WorkerController::class, 'assignedSite']);
    Route::get('my-shifts', [WorkerController::class, 'myShifts']);
    Route::get('offered-shifts', [WorkerController::class, 'offeredShifts']);
    Route::post('shifts/{id}/accept', [WorkerController::class, 'acceptShift']);
    Route::post('shifts/{id}/decline', [WorkerController::class, 'declineShift']);
    Route::get('check-calls', [WorkerController::class, 'checkCalls']);
    Route::post('check-calls/{id}/respond', [WorkerController::class, 'respondToCheckCall']);
    Route::get('alarms', [WorkerController::class, 'alarmHistory']);
    Route::post('alarms', [WorkerController::class, 'raiseAlarm']);
    Route::post('clock-in', [WorkerController::class, 'clockIn']);
    Route::post('clock-out', [WorkerController::class, 'clockOut']);
    Route::post('shift/start', [WorkerController::class, 'startShift']);
    Route::post('shift/{id}/end', [WorkerController::class, 'endShift']);
    Route::get('recent-checkins', [WorkerController::class, 'recentCheckins']);
    Route::get('tasks', [WorkerController::class, 'tasks']);
    Route::post('tasks/{id}/status', [WorkerController::class, 'updateTaskStatus']);
    Route::get('attendance', [WorkerController::class, 'attendance']);
    Route::get('activities', [WorkerController::class, 'activities']);
    Route::get('reports', [WorkerController::class, 'reports']);
    Route::post('reports', [WorkerController::class, 'submitReport']);
    Route::get('notifications', [WorkerController::class, 'notifications']);
    Route::post('notifications/read-all', [WorkerController::class, 'markAllNotificationsRead']);
    Route::post('notifications/{id}/read', [WorkerController::class, 'markNotificationRead']);
    Route::delete('notifications/{id}', [WorkerController::class, 'deleteNotification']);
    Route::get('team-messages', [TeamMessageController::class, 'index']);
    Route::post('team-messages/{id}/mark-read', [TeamMessageController::class, 'markRead']);
    Route::get('team-messages/{id}/replies', [TeamMessageController::class, 'myReplies']);
    Route::post('team-messages/{id}/replies', [TeamMessageController::class, 'storeReply']);
});


Route::middleware(['auth:sanctum'])->prefix('supervisor')->group(function () {
    Route::get('dashboard', [SupervisorController::class, 'dashboard']);
    Route::get('active-site-visit', [SupervisorController::class, 'activeSiteVisit']);
    Route::get('assigned-sites', [SupervisorController::class, 'assignedSites']);
    Route::get('workers', [SupervisorController::class, 'workers']);
    Route::get('statistics', [SupervisorController::class, 'statistics']);
    Route::get('all-officers', [SupervisorController::class, 'allOfficers']);
    Route::get('recent-reports', [SupervisorController::class, 'recentReports']);
    Route::get('reports/{id}', [SupervisorController::class, 'getReport']);
    Route::post('reports', [SupervisorController::class, 'submitReport']);
    Route::post('reports/{id}/review', [SupervisorController::class, 'reviewReport']);
    Route::post('site-visits/start', [SupervisorController::class, 'startSiteVisit']);
    Route::post('site-visits/end', [SupervisorController::class, 'endSiteVisit']);
    Route::get('notifications', [SupervisorController::class, 'notifications']);
    Route::post('notifications/read-all', [SupervisorController::class, 'markAllNotificationsRead']);
    Route::post('notifications/{id}/read', [SupervisorController::class, 'markNotificationRead']);
    Route::delete('notifications/{id}', [SupervisorController::class, 'deleteNotification']);
    Route::post('workers/{id}/tasks/assign', [SupervisorController::class, 'assignTask']);
    Route::post('workers/{id}/check-calls', [SupervisorController::class, 'sendCheckCall']);
    Route::post('checkins', [SupervisorController::class, 'submitCheckin']);
    Route::post('qr-scans', [SupervisorController::class, 'saveQrScan']);
    Route::get('alarms', [SupervisorController::class, 'alarmHistory']);
    Route::post('alarms', [SupervisorController::class, 'raiseAlarm']);
    Route::get('team-messages', [TeamMessageController::class, 'index']);
    Route::post('team-messages/{id}/mark-read', [TeamMessageController::class, 'markRead']);
    Route::get('team-messages/{id}/replies', [TeamMessageController::class, 'myReplies']);
    Route::post('team-messages/{id}/replies', [TeamMessageController::class, 'storeReply']);
});
<?php

declare(strict_types=1);

return [
    'title' => 'Users',
    'text' => 'View and manage all user accounts in the application.',
    'actions' => [
        'create' => 'Create User',
        'edit' => 'Edit User',
        'delete' => 'Delete User',
        'update' => 'Update User',
        'verify_email' => 'Verify Email',
    ],
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'role' => 'Role',
        'package' => 'Package',
        'monitorings' => 'Monitorings',
        'monitoring_limit' => 'Monitoring Limit',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],
    'messages' => [
        'user_created' => 'User created successfully.',
        'user_updated' => 'User updated successfully.',
        'user_deleted' => 'User deleted successfully.',
        'user_verified' => 'User verified successfully.',
        'cannot_edit_self' => 'You cannot edit yourself.',
        'cannot_delete_self' => 'You cannot delete yourself.',
        'empty' => 'No users found.',
        'email_verified' => 'Email is verified.',
        'email_unverified' => 'Email is not verified.',
    ],
    'delete' => [
        'title' => 'Delete User',
        'text' => 'Once a user is deleted, all of their resources and data will be permanently deleted.',
        'confirmation_question' => 'Are you sure you want to delete this user?',
        'confirmation_warning' => 'Once a user is deleted, all of their resources and data will be permanently deleted. Please confirm your action.',
    ],
];

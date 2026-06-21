<?php

declare(strict_types=1);

return [
    'title' => 'Teams',
    'create' => [
        'title' => 'Create team',
    ],
    'edit' => [
        'title' => 'Edit :team',
    ],
    'fields' => [
        'name' => 'Name',
        'description' => 'Description',
        'email' => 'Email',
        'role' => 'Role',
        'members' => 'Members',
        'monitorings' => 'Monitorings',
        'created_at' => 'Created',
    ],
    'roles' => [
        'admin' => 'Admin',
        'member' => 'Member',
    ],
    'messages' => [
        'created' => 'Team created.',
        'updated' => 'Team updated.',
        'deleted' => 'Team deleted.',
        'member_updated' => 'Team member updated.',
        'member_removed' => 'Team member removed.',
        'left' => 'You left the team.',
        'invitation_sent' => 'Invitation sent.',
        'invitation_revoked' => 'Invitation revoked.',
        'invitation_accepted' => 'Invitation accepted.',
        'login_to_accept' => 'Log in or register with the invited email address to accept the team invitation.',
    ],
    'validation' => [
        'last_admin' => 'A team must always have at least one admin.',
        'already_member' => 'This email address already belongs to a team member.',
        'email_mismatch' => 'This invitation was issued for a different email address.',
        'not_admin' => 'You must be a team admin for this action.',
        'not_member' => 'You are not a member of this team.',
        'delete_last_admin' => 'This user is the last admin of :team.',
    ],
    'actions' => [
        'create' => 'Create team',
        'edit' => 'Edit team',
        'delete' => 'Delete team',
        'invite' => 'Invite member',
        'revoke' => 'Revoke',
        'remove' => 'Remove',
        'leave' => 'Leave team',
        'save_role' => 'Save role',
    ],
    'sections' => [
        'members' => 'Members',
        'invitations' => 'Pending invitations',
        'invite' => 'Invite member',
        'details' => 'Team details',
        'notification_preferences' => 'Your notification preferences',
    ],
    'empty' => [
        'teams' => 'No teams yet.',
        'invitations' => 'No pending invitations.',
    ],
    'ownership' => [
        'private' => 'Private',
        'team' => 'Team',
        'select_label' => 'Ownership',
        'private_help' => 'Private monitorings are visible only to you.',
        'team_help' => 'Team monitorings are visible to all team members. Only team admins can edit them.',
        'move_to_team' => 'Move to team',
        'move_to_private' => 'Move to private',
        'moved_to_team' => 'Monitoring moved to team.',
        'moved_to_private' => 'Monitoring moved to private ownership.',
        'no_admin_teams' => 'You are not an admin of any team yet.',
    ],
    'mail' => [
        'invitation' => [
            'subject' => 'Invitation to join :team',
            'heading' => 'You have been invited to :team',
            'intro' => 'Accept this invitation to view team monitorings for :team.',
            'action' => 'Accept invitation',
            'expires' => 'This invitation expires on :date.',
            'outro' => 'If you did not expect this invitation, you can ignore this email.',
        ],
    ],
];

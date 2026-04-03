<script setup>
// Pages/Admin/Tickets/Trashed.vue
// Path: resources/js/Pages/Admin/Tickets/Trashed.vue
// Mirrors: resources/views/admin/tickets/trashed.blade.php
//
// Restore uses useForm POST. Force-delete uses router.delete().

import AppLayout from '../../../Layouts/AppLayout.vue'
import NavAdmin from '../../../Components/Nav/NavAdmin.vue'
import { Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  tickets: Object,
})

function restore(id) {
  useForm({}).post(`/admin/tickets/${id}/restore`)
}

function forceDelete(ticketNumber, id) {
  if (confirm(`Are you sure? This will PERMANENTLY delete ticket ${ticketNumber} and cannot be undone!`)) {
    router.delete(`/admin/tickets/${id}/force-delete`)
  }
}

function diffForHumans(d) {
  if (!d) return '—'
  const seconds = Math.floor((Date.now() - new Date(d)) / 1000)
  if (seconds < 60)    return `${seconds}s ago`
  if (seconds < 3600)  return `${Math.floor(seconds/60)}m ago`
  if (seconds < 86400) return `${Math.floor(seconds/3600)}h ago`
  return `${Math.floor(seconds/86400)}d ago`
}
</script>

<template>
  <AppLayout>
    <template #nav><NavAdmin /></template>

    <div class="container">
      <Link href="/admin/tickets" class="back-btn">← Back to Ticket Management</Link>

      <div class="page-header">
        <div>
          <h1>Deleted Tickets</h1>
          <p style="color:#9ca3af;">View and restore cancelled tickets</p>
        </div>
      </div>

      <!-- Info banner -->
      <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);padding:1rem;border-radius:8px;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;gap:0.75rem;color:#ef4444;">
          <span style="font-size:1.5rem;">🗑️</span>
          <div>
            <strong>Deleted Tickets Archive:</strong> These tickets were cancelled by users before assignment.
            You can restore them or permanently delete them.
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Ticket ID</th>
              <th>Issue</th>
              <th>Reporter</th>
              <th>Location</th>
              <th>Category</th>
              <th>Priority</th>
              <th>Deleted</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="tickets.data.length">
              <tr v-for="ticket in tickets.data" :key="ticket.id">
                <td class="ticket-id">{{ ticket.ticket_number }}</td>
                <td><strong>{{ ticket.title }}</strong></td>
                <td>{{ ticket.reporter?.first_name }} {{ ticket.reporter?.last_name }}</td>
                <td>{{ ticket.equipment?.lab?.name }}, {{ ticket.equipment?.equipment_code }}</td>
                <td>{{ ticket.category.charAt(0).toUpperCase() + ticket.category.slice(1) }}</td>
                <td>
                  <span class="priority-badge" :class="`priority-${ticket.priority}`">
                    {{ ticket.priority === 'high' ? '🔴' : ticket.priority === 'medium' ? '🟡' : '🟢' }}
                    {{ ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1) }}
                  </span>
                </td>
                <td>{{ diffForHumans(ticket.deleted_at) }}</td>
                <td>
                  <div class="action-menu">
                    <button type="button" class="action-btn" style="color:#10b981;" @click="restore(ticket.id)">
                      Restore
                    </button>
                    <button type="button" class="action-btn" style="color:#ef4444;" @click="forceDelete(ticket.ticket_number, ticket.id)">
                      Delete Forever
                    </button>
                  </div>
                </td>
              </tr>
            </template>
            <tr v-else>
              <td colspan="8" style="text-align:center;padding:3rem;color:#9ca3af;">
                <div style="font-size:2rem;margin-bottom:1rem;">✓</div>
                <h3 style="color:#d1d5db;">No Deleted Tickets</h3>
                <p>All clear! There are no cancelled tickets in the trash.</p>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="tickets.last_page > 1" class="pagination">
          <div class="page-info">
            Showing {{ tickets.from ?? 0 }}–{{ tickets.to ?? 0 }} of {{ tickets.total }} deleted tickets
          </div>
          <div class="page-controls">
            <button class="page-btn" :disabled="!tickets.prev_page_url" @click="router.get(tickets.prev_page_url)">← Previous</button>
            <button
              v-for="p in tickets.last_page" :key="p"
              class="page-btn" :class="{ active: p === tickets.current_page }"
              @click="router.get(tickets.path + '?page=' + p)"
            >{{ p }}</button>
            <button class="page-btn" :disabled="!tickets.next_page_url" @click="router.get(tickets.next_page_url)">Next →</button>
          </div>
        </div>
      </div>

      <!-- Summary cards -->
      <div style="margin-top:2rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem;">
        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);padding:1.5rem;border-radius:8px;">
          <h3 style="color:#ef4444;margin-bottom:0.5rem;font-size:1rem;">Total Deleted</h3>
          <div style="font-size:2rem;font-weight:bold;color:#ffffff;">{{ tickets.total }}</div>
          <p style="color:#9ca3af;font-size:0.9rem;margin-top:0.5rem;">Tickets in trash</p>
        </div>
        <div style="background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.3);padding:1.5rem;border-radius:8px;">
          <h3 style="color:#3b82f6;margin-bottom:0.5rem;font-size:1rem;">Storage Info</h3>
          <p style="color:#d1d5db;font-size:0.9rem;line-height:1.6;">Deleted tickets are kept for recovery. Permanently delete them to free up space.</p>
        </div>
        <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);padding:1.5rem;border-radius:8px;">
          <h3 style="color:#10b981;margin-bottom:0.5rem;font-size:1rem;">Restore Options</h3>
          <p style="color:#d1d5db;font-size:0.9rem;line-height:1.6;">Restoring a ticket will make it active again and visible to all users.</p>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
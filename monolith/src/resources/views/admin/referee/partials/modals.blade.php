<!-- Update Minute Modal -->
<div id="updateMinuteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Update Match Minute</h3>
            <div class="mt-2 px-7 py-3">
                <form action="{{ route('admin.referee.matches.update-minute', $match ?? null) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Minute</label>
                        <input type="number" name="current_minute" min="0" max="120" required
                               value="{{ $match->current_minute ?? 0 }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal('updateMinuteModal')"
                                class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Score Modal -->
<div id="updateScoreModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Update Score</h3>
            <div class="mt-2 px-7 py-3">
                <form action="{{ route('admin.referee.matches.update-score', $match ?? null) }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Home Score</label>
                            <input type="number" name="home_score" min="0" required
                                   value="{{ $match->home_score ?? 0 }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Away Score</label>
                            <input type="number" name="away_score" min="0" required
                                   value="{{ $match->away_score ?? 0 }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal('updateScoreModal')"
                                class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function showModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}
</script>

import { Plus, PauseCircle, PlayCircle, Settings2 } from "lucide-react";

export default function HabitsPage() {
  const habits = [
    { id: 1, name: "Morning Run", category: "Health", active: true },
    { id: 2, name: "Reading", category: "Learning", active: true },
    { id: 3, name: "Meditation", category: "Mindfulness", active: false },
    { id: 4, name: "Code Practice", category: "Career", active: true },
  ];

  const activeCount = habits.filter(h => h.active).length;

  return (
    <div className="space-y-6 relative min-h-screen pb-12">
      {/* Header with Gradient Glow */}
      <div className="relative mb-8">
        <div className="absolute -inset-2 bg-linear-to-r from-blue-500 via-indigo-600 to-purple-600 rounded-2xl blur-xl opacity-20" />

        <div className="relative">
          <p className="text-2xl font-black leading-tight mb-2 bg-linear-to-br from-slate-800 via-slate-700 to-slate-600 bg-clip-text text-transparent">
            Your System
          </p>
          <p className="text-sm text-slate-500 font-medium flex items-center gap-2">
            <span className="w-8 h-0.5 bg-linear-to-r from-indigo-500 to-transparent rounded-full" />
            Manage your daily rituals
          </p>
        </div>
      </div>

      {/* Overview Stat Card with Integrated Add Button */}
      <div className="relative rounded-3xl bg-white shadow-md p-6 overflow-hidden mb-8 border border-slate-50">
        <div className="absolute inset-0 bg-linear-to-br from-indigo-500/10 to-transparent" />
        <div className="relative flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-11 h-11 rounded-xl bg-slate-900 flex items-center justify-center shadow-lg shadow-slate-900/20">
              <Settings2 className="text-white w-5 h-5" />
            </div>
            <div>
              <p className="text-xs opacity-60 font-medium tracking-wide uppercase">Active Habits</p>
              <p className="font-bold text-xl text-slate-800">
                {activeCount} Rituals Running
              </p>
            </div>
          </div>

          {/* Square Add Button */}
          <button className="w-11 h-11 rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/30 flex items-center justify-center active:scale-90 transition-all hover:bg-blue-700">
            <Plus className="w-6 h-6 stroke-[3px]" />
          </button>
        </div>
      </div>

      {/* Habit List */}
      <div className="space-y-3">
        {habits.map((habit) => (
          <div
            key={habit.id}
            className={`rounded-2xl bg-white shadow-sm p-5 flex items-center justify-between transition-all border border-transparent ${
              !habit.active ? "opacity-50 grayscale-[0.5]" : "border-slate-50 hover:shadow-md"
            }`}
          >
            <div className="flex items-center gap-4">
              <div className={`w-1.5 h-8 rounded-full ${habit.active ? 'bg-blue-600' : 'bg-slate-300'}`} />
              <div>
                <p className={`font-bold text-slate-800 ${!habit.active ? 'text-slate-500' : ''}`}>
                  {habit.name}
                </p>
                <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                  {habit.category}
                </p>
              </div>
            </div>

            <div className="flex items-center">
              {habit.active ? (
                <div className="flex items-center gap-1.5 px-3 py-1 rounded-lg bg-blue-50 text-blue-600 border border-blue-100">
                  <PlayCircle className="w-4 h-4" />
                  <span className="text-[10px] font-black uppercase">Active</span>
                </div>
              ) : (
                <div className="flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-100 text-slate-500 border border-slate-200">
                  <PauseCircle className="w-4 h-4" />
                  <span className="text-[10px] font-black uppercase">Paused</span>
                </div>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
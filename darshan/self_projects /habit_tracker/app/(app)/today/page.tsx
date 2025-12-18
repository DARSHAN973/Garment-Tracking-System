import { CheckCircle, Clock, Flame } from "lucide-react";

export default function TodayPage() {
  const habits = [
    { id: 1, name: "Morning Run", category: "Health", duration: "20 min", completed: false },
    { id: 2, name: "Reading", category: "Learning", duration: "30 min", completed: true },
    { id: 3, name: "Meditation", category: "Mindfulness", duration: "15 min", completed: false },
    { id: 4, name: "Code Practice", category: "Career", duration: "45 min", completed: true },
    { id: 5, name: "Evening Walk", category: "Health", duration: "25 min", completed: false },
  ];

  const completedCount = habits.filter(h => h.completed).length;
  const totalCount = habits.length;

  return (
    <div className="space-y-6">
      {/* Header with Gradient Glow */}
      <div className="relative mb-4">
        {/* Gradient Glow */}
        <div className="absolute -inset-2 bg-linear-to-r from-blue-500 via-indigo-600 to-purple-600 rounded-2xl blur-xl opacity-20" />

        {/* Content */}
        <div className="relative">
          <p className="text-2xl font-black leading-tight mb-2 bg-linear-to-br from-slate-800 via-slate-700 to-slate-600 bg-clip-text text-transparent">
            Today&apos;s Mission
          </p>
          <p className="text-sm text-slate-500 font-medium flex items-center gap-2">
            <span className="w-8 h-0.5 bg-linear-to-r from-indigo-500 to-transparent rounded-full" />
            Focus on showing up
          </p>
        </div>
      </div>

      {/* Today's Progress Card */}
      <div className="relative rounded-3xl bg-white shadow-md p-6 overflow-hidden">
        <div className="absolute inset-0 bg-linear-to-br from-blue-500/10 to-transparent" />
        <div className="relative">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-3">
              <div className="w-11 h-11 rounded-xl bg-blue-600 flex items-center justify-center">
                <Flame className="text-white" />
              </div>
              <div>
                <p className="text-xs opacity-60">Today&apos;s Progress</p>
                <p className="font-semibold">{completedCount} of {totalCount} completed</p>
              </div>
            </div>

            <div className="text-right">
              <p className="text-3xl font-extrabold text-blue-600 leading-none">
                {Math.round((completedCount / totalCount) * 100)}%
              </p>
            </div>
          </div>

          {/* Progress Bar */}
          <div className="h-2 rounded-full bg-black/10 overflow-hidden">
            <div 
              className="h-full bg-blue-600 rounded-full transition-all duration-500" 
              style={{ width: `${(completedCount / totalCount) * 100}%` }}
            />
          </div>
        </div>
      </div>

      {/* Habit List */}
      <div className="space-y-3">
        {habits.map((habit) => (
          <div
            key={habit.id}
            className={`rounded-2xl bg-white shadow-sm p-5 flex items-center justify-between transition-all ${
              habit.completed ? 'opacity-60' : ''
            }`}
          >
            <div className="flex items-center gap-4">
              <div
                className={`w-6 h-6 rounded-full flex items-center justify-center transition-all ${
                  habit.completed
                    ? 'bg-blue-600'
                    : 'border-2 border-blue-600'
                }`}
              >
                {habit.completed && <CheckCircle className="w-5 h-5 text-white" />}
              </div>
              <div>
                <p className={`font-semibold ${habit.completed ? 'line-through' : ''}`}>
                  {habit.name}
                </p>
                <div className="flex items-center gap-2 mt-1">
                  <p className="text-xs opacity-60">{habit.category}</p>
                  <span className="text-xs opacity-40">•</span>
                  <p className="text-xs opacity-60 flex items-center gap-1">
                    <Clock className="w-3 h-3" />
                    {habit.duration}
                  </p>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Motivational Footer */}
      <div className="rounded-2xl bg-white shadow-sm p-5 flex items-center justify-between">
        <div>
          <p className="text-xs text-blue-600 font-medium">Remember</p>
          <p className="font-semibold mt-0.5">Even one habit done today is progress.</p>
        </div>

        <div className="w-8 h-8 rounded-full bg-blue-600/10 flex items-center justify-center">
          <span className="text-blue-600 font-semibold">✓</span>
        </div>
      </div>
    </div>
  );
}
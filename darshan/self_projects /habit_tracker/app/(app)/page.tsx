import { TrendingUp, AlertCircle, Award } from "lucide-react";

export default function DashboardPage() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="relative mb-4">
        {/* Gradient Glow */}
        <div className="absolute -inset-2 bg-linear-to-r from-blue-500 via-indigo-600 to-purple-600 rounded-2xl blur-xl opacity-20" />

        {/* Content */}
        <div className="relative">
          <p className="text-2xl font-black leading-tight mb-2 bg-linear-to-br from-slate-800 via-slate-700 to-slate-600 bg-clip-text text-transparent">
            Discipline decides your future.
          </p>
          <p className="text-sm text-slate-500 font-medium flex items-center gap-2">
            <span className="w-8 h-0.5 bg-linear-to-r from-indigo-500 to-transparent rounded-full" />
            Today counts.
          </p>
        </div>
      </div>

      {/* LEVEL — HERO */}
      <div className="relative rounded-3xl bg-white shadow-md p-6 overflow-hidden">
        <div className="absolute inset-0 bg-linear-to-br from-blue-500/10 to-transparent" />
        <div className="relative">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-11 h-11 rounded-xl bg-blue-600 flex items-center justify-center">
              <Award className="text-white" />
            </div>
            <div>
              <p className="text-xs opacity-60">Current Level</p>
              <p className="font-semibold">Habit Mastery</p>
            </div>
          </div>

          <div className="flex items-end gap-3">
            <h2 className="text-5xl font-extrabold text-blue-600 leading-none">
              4
            </h2>
            <div>
              <p className="text-xl font-bold">Disciplined</p>
              <p className="text-xs opacity-60">
                Weekly score <span className="font-semibold">78</span>
              </p>
            </div>
          </div>

          {/* Progress */}
          <div className="mt-5">
            <div className="flex justify-between text-xs opacity-60 mb-1">
              <span>Progress to Level 5</span>
              <span>78%</span>
            </div>
            <div className="h-2 rounded-full bg-black/10 overflow-hidden">
              <div className="h-full w-[78%] bg-blue-600 rounded-full" />
            </div>
          </div>
        </div>
      </div>

      {/* WEEKLY PROGRESS */}
      <div className="rounded-2xl bg-white shadow-sm p-6 flex items-center justify-between">
        <div>
          <p className="text-sm opacity-60">This Week</p>
          <p className="text-4xl font-bold">78%</p>
          <p className="text-sm opacity-60">Completion</p>
        </div>

        <div className="w-20 h-20 rounded-full border-[6px] border-blue-500/20 flex items-center justify-center font-semibold">
          78%
        </div>
      </div>

      {/* TREND */}
      <div className="rounded-2xl bg-white shadow-sm p-6">
        <div className="flex items-center gap-2 mb-4">
          <TrendingUp className="text-blue-600" />
          <p className="text-sm opacity-60">Last 5 Weeks</p>
        </div>
        <div className="flex items-end gap-3 h-28">
          {[42, 55, 48, 68, 78].map((v, i) => (
            <div
              key={i}
              style={{ height: `${v}%` }}
              className="w-4 rounded-full bg-blue-500/40"
            />
          ))}
        </div>
      </div>

      {/* INSIGHT */}
      <div className="rounded-2xl bg-white shadow-sm p-6 flex gap-4">
        <div className="w-10 h-10 rounded-lg bg-red-500/10 flex items-center justify-center">
          <AlertCircle className="text-red-600" />
        </div>
        <div>
          <p className="text-xs opacity-60">Needs Attention</p>
          <p className="font-semibold">Morning Run</p>
          <p className="text-xs opacity-60">Missed 4 times this week</p>
        </div>
      </div>

      <div className="rounded-2xl bg-white shadow-sm p-4 flex items-center justify-between">
        <div>
          <p className="text-xs text-blue-600 font-medium">Today’s focus</p>
          <p className="font-semibold mt-0.5">Show up for your hardest habit</p>
        </div>

        <div className="w-8 h-8 rounded-full bg-blue-600/10 flex items-center justify-center">
          <span className="text-blue-600 font-semibold">→</span>
        </div>
      </div>
    </div>
  );
}

import { LogOut, Shield, Info, User, ChevronRight } from "lucide-react";

export default function ProfilePage() {
  return (
    <div className="space-y-6 min-h-screen pb-12">
      {/* Header with Gradient Glow */}
      <div className="relative mb-8">
        <div className="absolute -inset-2 bg-linear-to-r from-blue-500 via-indigo-600 to-purple-600 rounded-2xl blur-xl opacity-20" />

        <div className="relative">
          <p className="text-2xl font-black leading-tight mb-2 bg-linear-to-br from-slate-800 via-slate-700 to-slate-600 bg-clip-text text-transparent">
            Your Space
          </p>
          <p className="text-sm text-slate-500 font-medium flex items-center gap-2">
            <span className="w-8 h-0.5 bg-linear-to-r from-indigo-500 to-transparent rounded-full" />
            Manage your personal system
          </p>
        </div>
      </div>

      {/* Primary User Card (Themed like the Habit Progress Card) */}
      <div className="relative rounded-3xl bg-white shadow-md p-6 overflow-hidden mb-8 border border-slate-50">
        <div className="absolute inset-0 bg-linear-to-br from-blue-500/10 to-transparent" />
        <div className="relative flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="w-14 h-14 rounded-2xl bg-slate-900 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-slate-900/20">
              D
            </div>
            <div>
              <p className="font-bold text-xl text-slate-800">Darshan</p>
              <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">Building discipline daily</p>
            </div>
          </div>

          <button className="w-10 h-10 rounded-xl bg-white border border-slate-100 shadow-sm flex items-center justify-center active:scale-90 transition-all hover:bg-slate-50">
            <User className="w-5 h-5 text-slate-600" />
          </button>
        </div>
      </div>

      {/* Philosophy Card with Subtle Tint */}
      <div className="relative rounded-3xl bg-white shadow-sm p-6 overflow-hidden border border-slate-50">
        <div className="absolute inset-0 bg-linear-to-tr from-indigo-500/5 to-transparent" />
        <div className="relative">
          <p className="text-[10px] font-black uppercase tracking-[0.2em] text-blue-600 mb-3">
            Philosophy
          </p>
          <p className="text-lg font-bold leading-tight text-slate-800">
            Discipline decides your future.
          </p>
          <p className="text-sm font-medium text-slate-500 mt-2 leading-relaxed">
            This system exists to help you show up, even when motivation fades.
          </p>
        </div>
      </div>

      {/* Action List */}
      <div className="space-y-3">
        {[
          { icon: <Shield className="w-5 h-5" />, label: "Privacy & Security" },
          { icon: <Info className="w-5 h-5" />, label: "About the App" },
        ].map((item, idx) => (
          <div
            key={idx}
            className="rounded-2xl bg-white shadow-sm p-5 flex items-center justify-between transition-all hover:shadow-md border border-slate-50 active:scale-[0.99]"
          >
            <div className="flex items-center gap-4">
              <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                {item.icon}
              </div>
              <p className="font-bold text-slate-700">{item.label}</p>
            </div>
            <ChevronRight className="w-5 h-5 text-slate-300" />
          </div>
        ))}
      </div>

      {/* Logout Button */}
      <button className="w-full mt-4 rounded-2xl bg-red-50 text-red-600 font-bold py-5 flex items-center justify-center gap-2 active:scale-95 transition-all hover:bg-red-100 border border-red-100/50">
        <LogOut className="w-5 h-5 stroke-[2.5px]" />
        <span className="uppercase tracking-wider text-xs">Terminate Session</span>
      </button>
    </div>
  );
}
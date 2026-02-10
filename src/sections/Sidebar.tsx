import { 
  Sparkles, 
  FolderOpen, 
  Send,
  BookOpen,
  Settings 
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface SidebarProps {
  activeTab: string;
  onTabChange: (tab: string) => void;
}

const navItems = [
  { id: 'create', icon: Sparkles, label: '创作' },
  { id: 'assets', icon: FolderOpen, label: '资产' },
  { id: 'publish', icon: Send, label: '发布' },
  { id: 'tutorial', icon: BookOpen, label: '教程' },
];

export default function Sidebar({ activeTab, onTabChange }: SidebarProps) {
  return (
    <aside className="fixed left-0 top-0 h-screen w-16 bg-white border-r border-[#E5E5E5] flex flex-col items-center py-4 z-50">
      {/* Logo */}
      <div className="mb-6">
        <div className="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
          <Sparkles className="w-5 h-5 text-white" />
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 flex flex-col items-center gap-1">
        {navItems.map((item) => {
          const Icon = item.icon;
          const isActive = activeTab === item.id;
          
          return (
            <button
              key={item.id}
              onClick={() => onTabChange(item.id)}
              className={cn(
                "w-10 h-10 rounded-lg flex items-center justify-center transition-all duration-200",
                "hover:bg-[#F5F5F5]",
                isActive && "bg-[#EEF2FF]"
              )}
              title={item.label}
            >
              <Icon 
                className={cn(
                  "w-5 h-5 transition-colors duration-200",
                  isActive ? "text-[#3B82F6]" : "text-[#666666]"
                )} 
              />
            </button>
          );
        })}
      </nav>

      {/* Settings */}
      <button 
        className="w-10 h-10 rounded-lg flex items-center justify-center transition-all duration-200 hover:bg-[#F5F5F5]"
        title="设置"
      >
        <Settings className="w-5 h-5 text-[#666666]" />
      </button>
    </aside>
  );
}

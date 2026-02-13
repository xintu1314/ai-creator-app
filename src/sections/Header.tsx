import { Zap, Diamond, Zap as ZapIcon, Bell, User } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface HeaderProps {
  onInspirationClick?: () => void;
}

export default function Header({ onInspirationClick }: HeaderProps) {
  return (
    <header className="h-14 bg-white border-b border-[#E5E5E5] flex items-center justify-between px-6 fixed top-0 left-16 right-0 z-40">
      {/* Left - Breadcrumb or Title */}
      <div className="flex items-center gap-2">
        <span className="text-sm text-[#666666]">首页</span>
        <span className="text-sm text-[#999999]">/</span>
        <span className="text-sm text-[#1A1A1A] font-medium">创作中心</span>
      </div>

      {/* Right - Actions */}
      <div className="flex items-center gap-2">
        {/* 灵感库 */}
        <button 
          type="button"
          onClick={onInspirationClick}
          className="h-9 px-3 text-sm text-[#666666] hover:text-[#1A1A1A] hover:bg-[#F5F5F5] rounded-lg transition-colors flex items-center gap-1.5"
        >
          <Zap className="w-4 h-4" />
          灵感库
        </button>

        {/* 充值 */}
        <div className="flex items-center gap-2 px-3 h-9">
          <ZapIcon className="w-4 h-4 text-[#3B82F6]" />
          <span className="text-sm text-[#1A1A1A] font-medium">287</span>
          <button className="text-sm text-[#3B82F6] hover:text-[#2563EB] transition-colors">
            充值
          </button>
        </div>

        <div className="w-px h-6 bg-[#E5E5E5] mx-1" />

        {/* 会员中心 */}
        <button 
          type="button"
          className="h-9 px-3 text-sm text-[#666666] hover:text-[#1A1A1A] hover:bg-[#F5F5F5] rounded-lg transition-colors flex items-center gap-1.5"
        >
          <Diamond className="w-4 h-4 text-amber-500" />
          会员中心
        </button>

        {/* 通知 */}
        <button 
          type="button"
          className="relative h-9 w-9 flex items-center justify-center text-[#666666] hover:text-[#1A1A1A] hover:bg-[#F5F5F5] rounded-lg transition-colors"
        >
          <Bell className="w-5 h-5" />
          <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
        </button>

        {/* 用户头像 */}
        <button 
          type="button"
          className="h-9 w-9 rounded-full bg-[#3B82F6] flex items-center justify-center text-white hover:bg-[#2563EB] transition-colors"
        >
          <User className="w-5 h-5" />
        </button>
      </div>
    </header>
  );
}

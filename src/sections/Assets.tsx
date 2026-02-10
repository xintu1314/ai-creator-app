import { useState } from 'react';
import { Image, Video } from 'lucide-react';
import { cn } from '@/lib/utils';

interface HistoryItem {
  id: string;
  title: string;
  image: string;
  type: 'image' | 'video';
  model: string;
  prompt: string;
  createdAt: string;
}

// 模拟历史记录数据
const mockHistory: HistoryItem[] = [
  {
    id: 'hist-1',
    title: '生成的图片1',
    image: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&h=500&fit=crop',
    type: 'image',
    model: 'banana pro',
    prompt: '一个美丽的风景画',
    createdAt: '2026-02-05 10:30',
  },
  {
    id: 'hist-2',
    title: '生成的视频1',
    image: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=500&fit=crop',
    type: 'video',
    model: '可灵',
    prompt: '一只蝴蝶在花丛中飞舞',
    createdAt: '2026-02-05 09:15',
  },
  {
    id: 'hist-3',
    title: '生成的图片2',
    image: 'https://images.unsplash.com/photo-1576919228236-a097c32a5cd4?w=400&h=500&fit=crop',
    type: 'image',
    model: 'banana pro',
    prompt: '圣诞主题的海报设计',
    createdAt: '2026-02-04 16:20',
  },
];

export default function Assets() {
  const [activeFilter, setActiveFilter] = useState<'all' | 'image' | 'video'>('all');

  const filteredHistory = activeFilter === 'all' 
    ? mockHistory 
    : mockHistory.filter(item => item.type === activeFilter);

  return (
    <div className="max-w-[1200px] mx-auto p-6">
      <h1 className="text-2xl font-semibold text-[#1A1A1A] mb-6">资产</h1>

      {/* Filter Tabs */}
      <div className="flex gap-4 border-b border-[#E5E5E5] mb-6">
        <button
          onClick={() => setActiveFilter('all')}
          className={cn(
            "pb-3 px-1 text-sm font-medium transition-all duration-200 relative",
            activeFilter === 'all' 
              ? "text-[#3B82F6]" 
              : "text-[#666666] hover:text-[#1A1A1A]"
          )}
        >
          全部
          {activeFilter === 'all' && (
            <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full" />
          )}
        </button>
        <button
          onClick={() => setActiveFilter('image')}
          className={cn(
            "pb-3 px-1 text-sm font-medium transition-all duration-200 relative flex items-center gap-2",
            activeFilter === 'image' 
              ? "text-[#3B82F6]" 
              : "text-[#666666] hover:text-[#1A1A1A]"
          )}
        >
          <Image className="w-4 h-4" />
          图片
          {activeFilter === 'image' && (
            <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full" />
          )}
        </button>
        <button
          onClick={() => setActiveFilter('video')}
          className={cn(
            "pb-3 px-1 text-sm font-medium transition-all duration-200 relative flex items-center gap-2",
            activeFilter === 'video' 
              ? "text-[#3B82F6]" 
              : "text-[#666666] hover:text-[#1A1A1A]"
          )}
        >
          <Video className="w-4 h-4" />
          视频
          {activeFilter === 'video' && (
            <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#3B82F6] rounded-full" />
          )}
        </button>
      </div>

      {/* History Grid */}
      {filteredHistory.length > 0 ? (
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
          {filteredHistory.map((item) => (
            <div
              key={item.id}
              className="group cursor-pointer bg-white rounded-xl overflow-hidden shadow-md hover:shadow-lg transition-all duration-300"
            >
              <div className="relative aspect-[3/4]">
                <img
                  src={item.image}
                  alt={item.title}
                  className="w-full h-full object-cover"
                />
                <div className="absolute top-2 left-2">
                  <span className={cn(
                    "px-2 py-0.5 text-[10px] rounded backdrop-blur-sm text-white",
                    item.type === 'image' 
                      ? "bg-blue-500/80" 
                      : "bg-purple-500/80"
                  )}>
                    {item.type === 'image' ? '图片' : '视频'}
                  </span>
                </div>
              </div>
              <div className="p-3">
                <p className="text-sm font-medium text-[#1A1A1A] mb-1 line-clamp-1">
                  {item.title}
                </p>
                <p className="text-xs text-[#666666] mb-1 line-clamp-1">
                  {item.model}
                </p>
                <p className="text-xs text-[#999999]">
                  {item.createdAt}
                </p>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="text-center py-20 text-[#666666]">
          <p>暂无历史记录</p>
        </div>
      )}
    </div>
  );
}
